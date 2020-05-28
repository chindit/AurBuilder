<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Package;
use App\Exception\PackageNotFoundException;
use App\Model\PackageInformation;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AurService
{
    private const BASE_URL = 'https://aur.archlinux.org/rpc?v=5';
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getPackageInformation(string $packageName): PackageInformation
    {
        $package = $this->makeRequest($packageName);

        return new PackageInformation(
            $package->first()['ID'],
            $package->first()['Name'],
            $package->first()['URLPath'],
            $package->first()['Version'],
            $package->first()['LastModified'],
            $package->first()['Description']
        );
    }

    /**
     * @return array<PackageInformation>
     */
    public function searchPackages(string $packageName): array
    {
        $packages = $this->makeRequest($packageName, 'search');

        $results = [];

        foreach ($packages as $package)
        {
            $results[] = new PackageInformation(
                $package['ID'],
                $package['Name'],
                $package['URLPath'],
                $package['Version'],
                $package['LastModified'],
                $package['Description']
            );
        }

        return $results;
    }

    public function findUpdatablePackages(Collection $packageList): Collection
    {
        $packageList = $packageList->keyBy('name');
        $packageNames = $packageList->keys();

        return $this
            ->makeRequest(implode('&arg[]=', $packageNames->toArray()))
            ->filter(
                fn(array $infos) =>
                version_compare(
                    $infos['Version'],
                    $packageList->get($infos['Name'], (new Package())->setVersion('0.0'))->getVersion()
                ) === 1
            )
            ->map(function(array $packageData) use ($packageList)
            {
                return new PackageInformation(
                    $packageData['ID'],
                    $packageData['Name'],
                    $packageData['URLPath'],
                    $packageData['Version'],
                    $packageData['LastModified'],
                    $packageData['Description'],
                    $packageList->get($packageData['Name'], (new Package())->setVersion('0.0'))->getVersion()
                );
            });
    }

    private function makeRequest(string $packageName, string $searchType='info'): Collection
    {
        try
        {
            $queryParams = $searchType === 'info' ? '&type=info&arg[]=' : '&type=search&arg=';
            $query = $this->httpClient->request('GET', self::BASE_URL . $queryParams . $packageName);

            $packageInformation = $query->toArray();
        } catch (HttpExceptionInterface | TransportException $exception) {
            throw new PackageNotFoundException(
                sprintf('Package %s haven\'t been found due to a network error.', $packageName)
            );
        }

        if (!isset($packageInformation['results']) || empty($packageInformation['results'])) {
            throw new PackageNotFoundException(sprintf('Package %s doesn\'t exist.', $packageName));
        }

        return new Collection($packageInformation['results']);
    }
}

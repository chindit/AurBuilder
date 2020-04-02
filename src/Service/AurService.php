<?php
declare(strict_types=1);

namespace App\Service;

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
            $package[0]['ID'],
            $package[0]['Name'],
            $package[0]['URLPath'],
            $package[0]['Version'],
            $package[0]['LastModified'],
            $package[0]['Description']
        );
    }

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

    private function makeRequest(string $packageName, string $searchType='info'): array
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

        return $packageInformation['results'];
    }
}

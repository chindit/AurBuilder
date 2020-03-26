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
        try
        {
            $query = $this->httpClient->request('GET', self::BASE_URL . '&type=info&arg[]=' . $packageName);

            $packageInformation = $query->toArray();
        } catch (HttpExceptionInterface | TransportException $exception) {
            throw new PackageNotFoundException(
                sprintf('Package %s haven\'t been found due to a network error.', $packageName)
            );
        }

        if (!isset($packageInformation['results']) || empty($packageInformation['results'])) {
            throw new PackageNotFoundException(sprintf('Package %s doesn\'t exist.', $packageName));
        }

        return new PackageInformation(
            $packageInformation['results'][0]['ID'],
            $packageInformation['results'][0]['Name'],
            $packageInformation['results'][0]['URLPath'],
            $packageInformation['results'][0]['Version'],
            $packageInformation['results'][0]['LastModified'],
            $packageInformation['results'][0]['Description']
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\FileSystemException;
use App\Exception\InvalidPackageException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ArchiveService
{
    private const BASE_URL = 'https://aur.archlinux.org/';
    private HttpClientInterface $httpClient;
    private Filesystem $filesystem;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->filesystem = new Filesystem();
    }

    public function prepareBuildFiles(string $url, string $name): string
    {
        $archiveRequest = $this->httpClient->request('GET', self::BASE_URL . $url);
        $fileName = tempnam(sys_get_temp_dir(), uniqid('', true));
        if ($fileName === false) {
            throw new FileSystemException('Unable to create temporary file');
        }

        file_put_contents($fileName, $archiveRequest->getContent());

        if ($this->filesystem->exists(sys_get_temp_dir() . '/' . $name)) {
            $this->filesystem->remove(sys_get_temp_dir() . '/' . $name);
        }

        $archive = new \PharData($fileName);
        $archive->extractTo(sys_get_temp_dir());

        if (!file_exists(sys_get_temp_dir() . '/' . $name . '/PKGBUILD')) {
            throw new InvalidPackageException(sprintf('Package %s is invalid.  No PKGBUILD found', $name));
        }

        return sys_get_temp_dir() . '/' . $name;
    }
}

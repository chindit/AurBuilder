<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\InvalidPackageException;
use Exception;
use PharData;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use UnexpectedValueException;

class ArchiveService
{
    private const BASE_URL = 'https://aur.archlinux.org/';
    private HttpClientInterface $httpClient;
    private Filesystem $filesystem;
    private string $buildDirectory;

    public function __construct(HttpClientInterface $httpClient, Filesystem $filesystem, string $buildDirectory)
    {
        $this->httpClient = $httpClient;
        $this->filesystem = $filesystem;
        $this->buildDirectory = $buildDirectory;
    }

    public function getBuildInformation(string $url, string $name): string
    {
        try
        {
            $fileName = $this->filesystem->tempnam(sys_get_temp_dir(), uniqid('', true));
        } catch (IOException $exception) {
            throw new InvalidPackageException(
                sprintf('Unable to create temporary file.  Error returned is following: %s', $exception->getMessage())
            );
        }

        try
        {
            $archiveRequest = $this->httpClient->request('GET', self::BASE_URL . $url);
            $result = file_put_contents($fileName, $archiveRequest->getContent());

            if ($result === false || !$this->filesystem->exists($fileName)) {
                throw new Exception();
            }
        } catch (Exception $exception) {
            throw new InvalidPackageException(sprintf('Unable to download and save package «%s»', $name));
        }

        if ($this->filesystem->exists(sys_get_temp_dir() . '/' . $name)) {
            $this->filesystem->remove(sys_get_temp_dir() . '/' . $name);
        }

        try
        {
            $archive = new PharData($fileName);
            $archive->extractTo(sys_get_temp_dir());
        } catch (UnexpectedValueException $exception) {
            throw new InvalidPackageException(sprintf('Package «%s» is not a valid archive or is corrupted', $name));
        }

        if (!file_exists(sys_get_temp_dir() . '/' . $name . '/PKGBUILD')) {
            throw new InvalidPackageException(sprintf('Package «%s» is invalid.  No PKGBUILD found', $name));
        }

        $this->moveFilesToBuildDirectory(sys_get_temp_dir() . '/' . $name);

        return sys_get_temp_dir() . '/' . $name;
    }

    private function moveFilesToBuildDirectory(string $directory): void
    {
    	$files = new Collection(scandir($this->buildDirectory));

    	$files->each(function(string $file) use ($directory)
	    {
	    	if (!is_dir($directory . '/' . $file))
		    {
			    $this->filesystem->copy($directory . '/' . $file, $this->buildDirectory . '/' . $file);
		    }
	    });
    }
}

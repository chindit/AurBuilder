<?php

namespace App\Service;

use App\Exception\FileSystemException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DockerService
{
    private string $buildDirectory;
    private string $dockerCli;
    private string $dockerCommand;
    private Filesystem $filesystem;

    public function __construct(
        string $buildDirectory,
        string $dockerCommandFile,
        string $dockerCli,
        Filesystem $filesystem)
    {
        $this->buildDirectory = $buildDirectory;
        $this->filesystem = $filesystem;
        $this->dockerCommand = $dockerCommandFile;
        $this->dockerCli = str_replace('{buildDirectory}', $buildDirectory, $dockerCli);
    }

    /**
     * @throws FileSystemException
     */
    public function prepareDocker(string $archivePath): void
    {
        $this->prepareDirectories();
        $this->copyFiles($archivePath);
    }

    public function buildPackage(SymfonyStyle $io): bool
    {
        $packageBuild = Process::fromShellCommandline($this->dockerCli);
        $packageBuild->setTimeout(0);

        $packageBuild->run(function($type, $buffer) use ($io) {
            $io->writeln($type . ':' . trim($buffer));
        });

        return $packageBuild->isSuccessful() && $this->packageExists();
    }

    private function prepareDirectories(): void
    {
        if (!$this->filesystem->exists($this->buildDirectory)) {
            try
            {
                $this->filesystem->mkdir($this->buildDirectory, 0744);
            } catch (IOException $exception) {
                throw new FileSystemException('Unable to create build directory');
            }
        }

        if (!is_writable($this->buildDirectory)) {
            throw new FileSystemException('Build directory must be writable');
        }
    }

    private function copyFiles(string $archivePath): void
    {
        try
        {
            $this->filesystem->copy($archivePath . '/PKGBUILD', $this->buildDirectory . '/PKGBUILD');
            $this->filesystem->chmod($this->buildDirectory . '/PKGBUILD', 0744);
            $this->filesystem->copy($this->dockerCommand, $this->buildDirectory . '/build.sh');
        } catch (IOException $exception) {
            throw new FileSystemException('Unable to copy build files');
        }
    }

    private function packageExists(): bool
    {
        /**
         * Scandir could return false and throw an E_WARNING.
         * This CANNOT happen since directory is checked in
         * prepareDirectories() method.  To avoid PHPStan
         * warning, we force type variable
         */
        /** @var array<string> $files */
        $files = scandir($this->buildDirectory);

        return count(array_filter($files, fn(string $filename) => substr($filename, -7) === '.tar.xz')) > 0;
    }
}

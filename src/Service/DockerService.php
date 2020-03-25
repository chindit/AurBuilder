<?php

namespace App\Service;

use App\Exception\FileSystemException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class DockerService
{
    private string $buildDirectory;
    private string $storageDirectory;
    private Filesystem $filesystem;

    public function __construct(string $buildDirectory, string $storageDirectory, Filesystem $filesystem)
    {
        $this->buildDirectory = $buildDirectory;
        $this->storageDirectory = $storageDirectory;
        $this->filesystem = $filesystem;
    }

    public function prepareDocker(string $archivePath): void
    {
        $this->prepareDirectories();
        $this->copyFiles($archivePath);
        $this->createScriptFile();
    }

    public function buildPackage(SymfonyStyle $io): bool
    {
        $packageBuild = Process::fromShellCommandline(
            'docker run -v '
            . $this->buildDirectory
            . ':/tmp/package archlinux /bin/bash -c "chmod +x /tmp/package/build.sh; /tmp/package/build.sh"'
        );
        $packageBuild->setTimeout(0);

        $packageBuild->run(function($type, $buffer) use ($io) {
            $io->writeln($type . ':' . trim($buffer));
        });

        return $packageBuild->isSuccessful() && $this->packageExists();
    }

    private function createScriptFile(): void
    {
        $dockerInstructions = [
            '#!/usr/bin/bash',
            'echo \'Refreshing package list\'',
            'echo "Server = https://archlinux.mailtunnel.eu/$repo/os/$arch" >> /etc/pacman.d/mirrorlist',
            'pacman -Sy',
            'echo \'Installing build utils\'',
            'pacman -S base-devel wget --noconfirm',
            'echo \'Creating build directory and setting rights\'',
            'useradd -d /home/packager -G root -m packager',
            'echo "packager ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers',
            'chown -R packager:users /home/packager',
            'cd /home/packager/',
            'echo \'Copying PKGBUILD\'',
            'cp /tmp/package/PKGBUILD .',
            'echo \'Starting build\'',
            'sudo -u packager makepkg -s --noconfirm',
            'echo \'Moving back package to exchange directory\'',
            'mv *.tar.gz /tmp/package/'
        ];

        if (file_put_contents($this->buildDirectory . '/build.sh', implode("\n", $dockerInstructions)) === 0) {
            throw new FileSystemException('Unable to write docker script');
        }
    }

    private function prepareDirectories(): void
    {
        if (!$this->filesystem->exists($this->buildDirectory)) {
            if (!mkdir($this->buildDirectory, 0744) && !is_dir($this->buildDirectory)) {
                throw new FileSystemException('Unable to create build directory');
            }
        }

        if (!$this->filesystem->exists($this->storageDirectory) || !is_writable($this->storageDirectory)) {
            throw new FileSystemException('Storage directory must exist and be writable');
        }
    }

    private function copyFiles(string $archivePath): void
    {
        $this->filesystem->copy($archivePath . '/PKGBUILD', $this->buildDirectory . '/PKGBUILD');
        $this->filesystem->chmod($this->buildDirectory . '/PKGBUILD', '0744');
    }

    private function packageExists(): bool
    {
        $files = scandir($this->buildDirectory);

        return count(array_filter($files, fn(string $filename) => substr($filename, -7) === '.tar.gz')) > 0;
    }
}

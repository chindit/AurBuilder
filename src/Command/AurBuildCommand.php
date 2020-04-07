<?php
declare(strict_types=1);

namespace App\Command;

use App\Exception\FileSystemException;
use App\Exception\InvalidPackageException;
use App\Exception\PackageNotFoundException;
use App\Service\ArchiveService;
use App\Service\AurService;
use App\Service\DockerService;
use App\Service\RepositoryService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AurBuildCommand extends Command
{
    protected static $defaultName = 'aur:build';

    private AurService $aurService;
    private ArchiveService $archiveService;
    private DockerService $dockerService;
    private RepositoryService $repositoryService;

    public function __construct(
        AurService $aurService,
        ArchiveService $archiveService,
        DockerService $dockerService,
        RepositoryService $repositoryService
    )
    {
        parent::__construct(self::$defaultName);
        $this->aurService = $aurService;
        $this->archiveService = $archiveService;
        $this->dockerService = $dockerService;
        $this->repositoryService = $repositoryService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Build an ArchLinux package based on an AUR package name')
            ->addArgument('package', InputArgument::REQUIRED, 'AUR Package name')
            ->addOption('update', '-u', InputOption::VALUE_OPTIONAL, 'Add package(s) to repository', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $packageName = $input->getArgument('package');
        if (!is_string($packageName)) {
            $io->error('Invalid package name');

            return 1;
        }

        $io->writeln(sprintf('Searching for package %s', $packageName));
        try {
            $package = $this->aurService->getPackageInformation($packageName);
        } catch (PackageNotFoundException $e) {
            $io->error($e->getMessage());

            return 1; // Failure
        }

        $io->writeln(sprintf('Package %s was found in version %s', $package->getName(), $package->getVersion()));

        $io->writeln('Downloading build information');
        try
        {
            $archivePath = $this->archiveService->getBuildInformation($package->getUrl(), $package->getName());

            $this->dockerService->prepareDocker($archivePath);

            $result = $this->dockerService->buildPackage($io);
        } catch (InvalidPackageException $exception) {
            $io->error(
                sprintf('Unable to prepare package for build.  Returned error is: %s', $exception->getMessage())
            );

            return 1;
        } catch (FileSystemException $exception) {
            $io->error(
                sprintf('Unable to prepare Docker service.  Returned error is: %s', $exception->getMessage())
            );

            return 2;
        }

        if ($result) {
            $io->success('Package successfully built');
        } else {
            $io->error('An error has occurred during build process');

            return 3;
        }

        if ($input->getOption('update') !== false) {
            if ($this->repositoryService->addPackagesToRepository($package)) {
                $io->success('Repository updated');
            } else {
                $io->error('Unable to update repository');

                return 4;
            }
        }

        return 0;
    }
}

<?php

namespace App\Command;

use App\Model\PackageInformation;
use App\Repository\PackageRepository;
use App\Repository\PackageRequestRepository;
use App\Service\AurService;
use App\Service\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AurCheckUpdatesCommand extends Command
{
    protected static $defaultName = 'aur:check-updates';

    private AurService $aurService;

    private PackageRepository $repository;

    private PackageRequestRepository $requestRepository;

    public function __construct(
        AurService $aurService,
        PackageRepository $repository,
        PackageRequestRepository $requestRepository
    )
    {
        parent::__construct();
        $this->aurService = $aurService;
        $this->repository = $repository;
        $this->requestRepository = $requestRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check if registered packages needs an update and update them if asked')
            ->addOption('update', '-u', InputOption::VALUE_OPTIONAL, 'Update listed packages', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $packagesToUpdate = $this->aurService->findUpdatablePackages(
            (new Collection($this->repository->findAll()))
                ->merge(new Collection($this->requestRepository->findBy(['approved' => true])))
        );

        $io->table(
            ['ID', 'Name', 'Version'],
            $packagesToUpdate->map(function(PackageInformation $package) {
                return [
                    $package->getId(),
                    $package->getName(),
                    $package->getVersion(),
                ];
            })->toArray()
        );

        if ($input->getOption('update') !== false) {
            $application = $this->getApplication();
            if ($application === null) {
                $io->error('No application have been found');

                return 1;
            }

            $buildCommand = $application->find('aur:build');

            foreach ($packagesToUpdate as $package) {
                $arguments = ['command' => 'aur:build', 'package' => $package->getName(), '-u' => true];

                $io->writeln(sprintf('Starting update of %s', $package->getName()));
                $buildCommand->run(new ArrayInput($arguments), $output);
            }
        }

        return 0;
    }
}

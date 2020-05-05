<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Package;
use App\Entity\Release;
use App\Model\PackageInformation;
use App\Repository\PackageRepository;
use App\Repository\PackageRequestRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RepositoryService
{
    private string $buildDirectory;
    private string $repositoryDir;
    private string $repositoryName;
    private string $repositoryCli;
    private Filesystem $filesystem;
    private EntityManagerInterface $entityManager;
    private PackageRepository $packageRepository;
    private PackageRequestRepository $packageRequestRepository;

    public function __construct(
        string $buildDirectory,
        string $repositoryDir,
        string $repositoryName,
        string $repositoryCli,
        Filesystem $filesystem,
        EntityManagerInterface $entityManager,
        PackageRepository $packageRepository,
        PackageRequestRepository $packageRequestRepository
    )
    {
        $this->buildDirectory = $buildDirectory;
        $this->repositoryDir = $repositoryDir;
        $this->repositoryName = $repositoryName;
        $this->filesystem = $filesystem;
        $this->entityManager = $entityManager;
        $this->packageRepository = $packageRepository;
        $this->repositoryCli = $repositoryCli;
        $this->packageRequestRepository = $packageRequestRepository;
    }

    public function addPackagesToRepository(PackageInformation $package): bool
    {
        /** @var array<string> $files */
        $files = scandir($this->buildDirectory);
        $packageFiles = new Collection($files);
        $movedFiles = new Collection();
        $isSuccessful = true;

        try
        {
            foreach ($packageFiles as $file)
            {
                if (stripos($file, '.tar.') > 0 && stripos($file, $package->getName()) !== false) {
                    $this->filesystem->copy($this->buildDirectory . '/' . $file, $this->repositoryDir . '/' . $file);
                    $this->filesystem->remove($this->buildDirectory . '/' . $file);
                    $movedFiles->push($file);

                    if ($isSuccessful) {
                        $isSuccessful = $this->updateRepository($file);
                        $this->updateEntities($package);
                    }
                }
            }
        } catch (\Exception $exception) {
            return false;
        }

        return $movedFiles->isNotEmpty() && $isSuccessful;
    }

    private function updateEntities(PackageInformation $packageInformation): void
    {
        $package = $this->packageRepository->findOneBy(['packageId' => $packageInformation->getId()]);

        if ($package === null) {
            $package = (new Package())
                ->setPackageId($packageInformation->getId())
                ->setName($packageInformation->getName())
                ->setDescription($packageInformation->getDescription())
                ->setVersion($packageInformation->getVersion());
            $this->entityManager->persist($package);
        } else {
            $package->setVersion($packageInformation->getVersion());
        }

        $packageRequest = $this->packageRequestRepository->findOneBy(['name' => $packageInformation->getName()]);
        if ($packageRequest !== null) {
            $this->entityManager->remove($packageRequest);
        }

        $release = (new Release())
            ->setLastVersion($package->getVersion())
            ->setNewVersion($packageInformation->getVersion())
            ->setName($package->getName());
        $this->entityManager->persist($release);

        $this->entityManager->flush();
    }

    private function updateRepository(string $fileName): bool
    {
        $command = str_replace(
            ['{repositoryDir}', '{repositoryName}', '{package}'],
            [$this->repositoryDir, $this->repositoryName, $fileName],
            $this->repositoryCli
        );

        $process = Process::fromShellCommandline($command);

        $process->run();

        return $process->isSuccessful();
    }
}

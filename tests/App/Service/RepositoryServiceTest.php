<?php

namespace App\Tests\App\Service;

use App\Entity\Package;
use App\Entity\PackageRequest;
use App\Entity\Release;
use App\Model\PackageInformation;
use App\Repository\PackageRepository;
use App\Repository\PackageRequestRepository;
use App\Service\RepositoryService;
use App\Tests\AbstractProphetTest;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryServiceTest extends AbstractProphetTest
{

    public function testAddPackagesWithoutAnyFile(): void
    {
        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            '',
            '',
            '',
            $this->prophet->prophesize(Filesystem::class)->reveal(),
            $this->prophet->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophet->prophesize(PackageRepository::class)->reveal(),
            $this->prophet->prophesize(PackageRequestRepository::class)->reveal()
        );

        $this->assertFalse(
            $repositoryService->addPackagesToRepository(
                new PackageInformation(
                    time(),
                    Uuid::getFactory()->uuid4(),
                    '',
                    '',
                    time(),
                    ''
                )
            )
        );
    }

    public function testAddPackageWithFilesAndError(): void
    {
        $fileName = Uuid::uuid4();
        touch(sys_get_temp_dir() . '/' . $fileName . '.tar.xz');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem->copy(
            Argument::exact(sys_get_temp_dir() . '/' . $fileName . '.tar.xz'),
            Argument::exact('chindit/' . $fileName . '.tar.xz')
        )
            ->shouldBeCalledOnce()
            ->willThrow(IOException::class);

        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            'chindit',
            '',
            '',
            $fileSystem->reveal(),
            $this->prophet->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophet->prophesize(PackageRepository::class)->reveal(),
            $this->prophet->prophesize(PackageRequestRepository::class)->reveal()
        );

        $this->assertFalse(
            $repositoryService->addPackagesToRepository(
                new PackageInformation(
                    time(),
                    $fileName,
                    '',
                    '',
                    time(),
                    ''
                )
            )
        );
    }

    public function testAddPackageWithFiles(): void
    {
        $fileName = Uuid::uuid4();
        $packageId = time();
        touch(sys_get_temp_dir() . '/' . $fileName . '.tar.xz');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem
            ->copy(
                Argument::exact(sys_get_temp_dir() . '/' . $fileName . '.tar.xz'),
                Argument::exact('chindit/' . $fileName . '.tar.xz')
            )
            ->shouldBeCalledOnce();
        $fileSystem
            ->remove(Argument::exact(sys_get_temp_dir() . '/' . $fileName . '.tar.xz'))
            ->shouldBeCalledOnce();

        $packageRepository = $this->prophet->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => $packageId]))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $entityManager = $this->prophet->prophesize(EntityManagerInterface::class);
        $entityManager
            ->persist(Argument::type(Package::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->persist(Argument::type(Release::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->flush()
            ->shouldBeCalledOnce();

        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            'chindit',
            '',
            '',
            $fileSystem->reveal(),
            $entityManager->reveal(),
            $packageRepository->reveal(),
            $this->prophet->prophesize(PackageRequestRepository::class)->reveal()
        );

        $this->assertTrue(
            $repositoryService->addPackagesToRepository(
                new PackageInformation(
                    $packageId,
                    $fileName,
                    '',
                    '',
                    time(),
                    ''
                )
            )
        );
    }

    public function testUpdateEntitiesWithNewPackageAndNoRequest(): void
    {
        $reflection = new \ReflectionClass(RepositoryService::class);
        $method = $reflection->getMethod('updateEntities');
        $method->setAccessible(true);

        $packageRepository = $this->prophet->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => '123']))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $packageRequestRepository = $this->prophet->prophesize(PackageRequestRepository::class);
        $packageRequestRepository
            ->findOneBy(Argument::exact(['name' => 'chindit']))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $entityManager = $this->prophet->prophesize(EntityManagerInterface::class);
        $entityManager
            ->persist(Argument::type(Package::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->persist(Argument::type(Release::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->flush()
            ->shouldBeCalledOnce();

        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            'chindit',
            '',
            '',
            $this->prophet->prophesize(Filesystem::class)->reveal(),
            $entityManager->reveal(),
            $packageRepository->reveal(),
            $packageRequestRepository->reveal()
        );

        $this->assertNull($method->invokeArgs(
            $repositoryService,
            [
                new PackageInformation(
                    123,
                    'chindit',
                    '',
                    '',
                    time(),
                    ''
                ),
            ]
        ));
    }

    public function testUpdateEntitiesWithNewPackageAndRequest(): void
    {
        $reflection = new \ReflectionClass(RepositoryService::class);
        $method = $reflection->getMethod('updateEntities');
        $method->setAccessible(true);

        $packageRepository = $this->prophet->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => '123']))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $packageRequest = new PackageRequest();
        $packageRequest->setName('chindit')
            ->setCreatedAt(Carbon::now());

        $packageRequestRepository = $this->prophet->prophesize(PackageRequestRepository::class);
        $packageRequestRepository
            ->findOneBy(Argument::exact(['name' => 'chindit']))
            ->shouldBeCalledOnce()
            ->willReturn($packageRequest);

        $entityManager = $this->prophet->prophesize(EntityManagerInterface::class);
        $entityManager
            ->persist(Argument::type(Package::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->persist(Argument::type(Release::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->remove(Argument::exact($packageRequest))
            ->shouldBeCalledOnce();
        $entityManager
            ->flush()
            ->shouldBeCalledOnce();

        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            'chindit',
            '',
            '',
            $this->prophet->prophesize(Filesystem::class)->reveal(),
            $entityManager->reveal(),
            $packageRepository->reveal(),
            $packageRequestRepository->reveal()
        );

        $this->assertNull(
            $method->invokeArgs(
                $repositoryService,
                [
                    new PackageInformation(
                        123,
                        'chindit',
                        '',
                        '',
                        time(),
                        ''
                    ),
                ]
            )
        );
    }

    public function testUpdateEntitiesWithExistingPackage(): void
    {
        $reflection = new \ReflectionClass(RepositoryService::class);
        $method = $reflection->getMethod('updateEntities');
        $method->setAccessible(true);

        $package = new Package();
        $package
            ->setName('chindit')
            ->setPackageId(123)
            ->setDescription('desc')
            ->setVersion('0.0.1');

        $packageRepository = $this->prophet->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => '123']))
            ->shouldBeCalledOnce()
            ->willReturn($package);

        $entityManager = $this->prophet->prophesize(EntityManagerInterface::class);
        $entityManager
            ->persist(Argument::type(Release::class))
            ->shouldBeCalledOnce();
        $entityManager
            ->flush()
            ->shouldBeCalledOnce();

        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            'chindit',
            '',
            '',
            $this->prophet->prophesize(Filesystem::class)->reveal(),
            $entityManager->reveal(),
            $packageRepository->reveal(),
            $this->prophet->prophesize(PackageRequestRepository::class)->reveal()
        );

        $method->invokeArgs(
            $repositoryService,
            [
                new PackageInformation(
                    123,
                    'chindit',
                    '',
                    '0.0.2',
                    time(),
                    ''
                ),
            ]
        );

        // Version should be updated by method
        $this->assertEquals('0.0.2', $package->getVersion());
    }

    public function testUpdateRepository(): void
    {
        $reflection = new \ReflectionClass(RepositoryService::class);
        $method = $reflection->getMethod('updateRepository');
        $method->setAccessible(true);

        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            'ls',
            '-a',
            '{repositoryDir} {repositoryName}{package}',
            $this->prophet->prophesize(Filesystem::class)->reveal(),
            $this->prophet->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophet->prophesize(PackageRepository::class)->reveal(),
            $this->prophet->prophesize(PackageRequestRepository::class)->reveal()
        );

        $this->assertTrue(
            $method->invokeArgs(
                $repositoryService,
                ['h']
            )
        );
    }
}

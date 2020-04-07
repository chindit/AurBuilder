<?php

namespace App\Tests\App\Service;

use App\Entity\Package;
use App\Entity\Release;
use App\Model\PackageInformation;
use App\Repository\PackageRepository;
use App\Service\RepositoryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryServiceTest extends TestCase
{
    public function testAddPackagesWithoutAnyFile(): void
    {
        $repositoryService = new RepositoryService(
            sys_get_temp_dir(),
            '',
            '',
            '',
            $this->prophesize(Filesystem::class)->reveal(),
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophesize(PackageRepository::class)->reveal()
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

        $fileSystem = $this->prophesize(Filesystem::class);
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
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophesize(PackageRepository::class)->reveal()
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

        $fileSystem = $this->prophesize(Filesystem::class);
        $fileSystem
            ->copy(
                Argument::exact(sys_get_temp_dir() . '/' . $fileName . '.tar.xz'),
                Argument::exact('chindit/' . $fileName . '.tar.xz')
            )
            ->shouldBeCalledOnce();
        $fileSystem
            ->remove(Argument::exact(sys_get_temp_dir() . '/' . $fileName . '.tar.xz'))
            ->shouldBeCalledOnce();

        $packageRepository = $this->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => $packageId]))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
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
            $packageRepository->reveal()
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

    public function testUpdateEntitiesWithNewPackage(): void
    {
        $reflection = new \ReflectionClass(RepositoryService::class);
        $method = $reflection->getMethod('updateEntities');
        $method->setAccessible(true);

        $packageRepository = $this->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => '123']))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
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
            $this->prophesize(Filesystem::class)->reveal(),
            $entityManager->reveal(),
            $packageRepository->reveal()
        );

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

        $packageRepository = $this->prophesize(PackageRepository::class);
        $packageRepository
            ->findOneBy(Argument::exact(['packageId' => '123']))
            ->shouldBeCalledOnce()
            ->willReturn($package);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
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
            $this->prophesize(Filesystem::class)->reveal(),
            $entityManager->reveal(),
            $packageRepository->reveal()
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
            $this->prophesize(Filesystem::class)->reveal(),
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophesize(PackageRepository::class)->reveal()
        );

        $this->assertTrue(
            $method->invokeArgs(
                $repositoryService,
                [
                    new PackageInformation(
                        123,
                        'l',
                        '',
                        '0.0.2',
                        time(),
                        ''
                    ),
                ]
            )
        );
    }
}

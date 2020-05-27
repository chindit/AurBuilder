<?php

namespace App\Tests\App\Command;

use App\Command\AurBuildCommand;
use App\Exception\FileSystemException;
use App\Exception\InvalidPackageException;
use App\Exception\PackageNotFoundException;
use App\Model\PackageInformation;
use App\Service\ArchiveService;
use App\Service\AurService;
use App\Service\DockerService;
use App\Service\RepositoryService;
use App\Tests\AbstractProphetTest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AurBuildCommandTest extends AbstractProphetTest
{
    public function testDefinitionAndArguments(): void
    {
        $aurCommand = new AurBuildCommand(
            $this->prophet->prophesize(AurService::class)->reveal(),
            $this->prophet->prophesize(ArchiveService::class)->reveal(),
            $this->prophet->prophesize(DockerService::class)->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $this->assertEquals('aur:build', $aurCommand->getName());
        $this->assertEquals('Build an ArchLinux package based on an AUR package name', $aurCommand->getDescription());
        $this->assertEquals(1, $aurCommand->getDefinition()->getArgumentCount());
        $this->assertTrue($aurCommand->getDefinition()->hasArgument('package'));
        $this->assertEquals(
            new InputArgument('package', InputArgument::REQUIRED, 'AUR Package name'),
            $aurCommand->getDefinition()->getArgument('package')
        );
    }

    public function testFailOnMissingArgument(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurCommand = new AurBuildCommand(
            $this->prophet->prophesize(AurService::class)->reveal(),
            $this->prophet->prophesize(ArchiveService::class)->reveal(),
            $this->prophet->prophesize(DockerService::class)->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $this->assertEquals(
            1,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $this->prophet->prophesize(InputInterface::class)->reveal(),
                    $this->getOutputInterface()->reveal()
                ]
            )
        );
    }

    public function testPackageNotFound(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willThrow(PackageNotFoundException::class);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $this->prophet->prophesize(ArchiveService::class)->reveal(),
            $this->prophet->prophesize(DockerService::class)->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');

        $this->assertEquals(
            1,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $this->getOutputInterface()->reveal()
                ]
            )
        );
    }

    public function testBuildFailsOnPackagePreparation(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willThrow(PackageNotFoundException::class);

        $archiveService = $this->prophet->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willThrow(InvalidPackageException::class);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $this->prophet->prophesize(DockerService::class)->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');

        $this->assertEquals(
            1,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $this->getOutputInterface()->reveal()
                ]
            )
        );
    }

    public function testBuildFailsOnDockerPreparation(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(
                new PackageInformation(
                    23,
                    'chindit',
                    'https://github.com/chindit/AurBuilder',
                    '1.3.2',
                    time(),
                    'Chindit\'s package'
                )
            );

        $archiveService = $this->prophet->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');

        $dockerService = $this->prophet->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker(Argument::any())
            ->shouldBeCalledOnce()
            ->willThrow(FileSystemException::class);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');

        $this->assertEquals(
            2,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $this->getOutputInterface()->reveal()
                ]
            )
        );
    }

    public function testBuildPackageFailure(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(
                new PackageInformation(
                    23,
                    'chindit',
                    'https://github.com/chindit/AurBuilder',
                    '1.3.2',
                    time(),
                    'Chindit\'s package'
                )
            );

        $archiveService = $this->prophet->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');
        $archiveService
            ->cleanDirectory(
                Argument::any()
            )
            ->shouldBeCalledOnce();

        $dockerService = $this->prophet->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker()
            ->shouldBeCalledOnce();
        $dockerService
            ->buildPackage(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');

        $outputInterface = $this->getOutputInterface();
        $outputInterface
            ->writeln(
                Argument::containingString('An error has occurred during build process'), Argument::exact(1)
            )
            ->shouldBecalledOnce();

        $this->assertEquals(
            3,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $outputInterface->reveal()
                ]
            )
        );
    }

    public function testBuildPackageSuccessWithoutUpdate(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(
                new PackageInformation(
                    23,
                    'chindit',
                    'https://github.com/chindit/AurBuilder',
                    '1.3.2',
                    time(),
                    'Chindit\'s package'
                )
            );

        $archiveService = $this->prophet->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');
        $archiveService
            ->cleanDirectory(
                Argument::any()
            )
            ->shouldBeCalledOnce();

        $dockerService = $this->prophet->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker()
            ->shouldBeCalledOnce();
        $dockerService
            ->buildPackage(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal(),
            $this->prophet->prophesize(RepositoryService::class)->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');
        $inputInterface
            ->getOption(Argument::exact('update'))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $outputInterface = $this->getOutputInterface();
        $outputInterface->writeln(Argument::containingString('Package successfully built'), Argument::exact(1))
            ->shouldBecalledOnce();

        $this->assertEquals(
            0,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $outputInterface->reveal()
                ]
            )
        );
    }

    public function testSuccessfulBuildWithFailedUpdate(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $package = new PackageInformation(
            23,
            'chindit',
            'https://github.com/chindit/AurBuilder',
            '1.3.2',
            time(),
            'Chindit\'s package'
        );
        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(
                $package
            );

        $archiveService = $this->prophet->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');
        $archiveService
            ->cleanDirectory(
                Argument::any()
            )
            ->shouldBeCalledOnce();

        $dockerService = $this->prophet->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker()
            ->shouldBeCalledOnce();
        $dockerService
            ->buildPackage(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $repositoryService = $this->prophet->prophesize(RepositoryService::class);
        $repositoryService
            ->addPackagesToRepository(Argument::exact($package))
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal(),
            $repositoryService->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');
        $inputInterface
            ->getOption(Argument::exact('update'))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $outputInterface = $this->getOutputInterface();
        $outputInterface->writeln(Argument::containingString('Package successfully built'), Argument::exact(1))
            ->shouldBeCalledOnce();
        $outputInterface->writeln(Argument::containingString('Unable to update repository'), Argument::exact(1))
            ->shouldBeCalledOnce();

        $this->assertEquals(
            4,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $outputInterface->reveal()
                ]
            )
        );
    }

    public function testSuccessfulBuildWithSuccessfulUpdate(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $package = new PackageInformation(
            23,
            'chindit',
            'https://github.com/chindit/AurBuilder',
            '1.3.2',
            time(),
            'Chindit\'s package'
        );

        $aurService = $this->prophet->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(
                $package
            );

        $archiveService = $this->prophet->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');
        $archiveService
            ->cleanDirectory(
                Argument::any()
            )
            ->shouldBeCalledOnce();

        $dockerService = $this->prophet->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker()
            ->shouldBeCalledOnce();
        $dockerService
            ->buildPackage(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $repositoryService = $this->prophet->prophesize(RepositoryService::class);
        $repositoryService
            ->addPackagesToRepository(Argument::exact($package))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal(),
            $repositoryService->reveal()
        );

        $inputInterface = $this->prophet->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');
        $inputInterface
            ->getOption(Argument::exact('update'))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $outputInterface = $this->getOutputInterface();
        $outputInterface->writeln(Argument::containingString('Package successfully built'), Argument::exact(1))
            ->shouldBeCalledOnce();
        $outputInterface->writeln(Argument::containingString('Repository updated'), Argument::exact(1))
            ->shouldBeCalledOnce();

        $this->assertEquals(
            0,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $inputInterface->reveal(),
                    $outputInterface->reveal()
                ]
            )
        );
    }

    private function getOutputInterface(): ObjectProphecy
    {
        $outputFormatter = $this->prophet->prophesize(OutputFormatterInterface::class);
        $outputFormatter->format(Argument::any())
            ->willReturnArgument();
        $outputFormatter->isDecorated()
            ->willReturn(false);
        $outputFormatter->setDecorated(Argument::exact(false))
            ->shouldBeCalled();

        $outputInterface = $this->prophet->prophesize(OutputInterface::class);
        $outputInterface->getFormatter()
                ->shouldBeCalled()
            ->willReturn(
                $outputFormatter->reveal()
            );
        $outputInterface->getVerbosity()
            ->shouldBeCalled()
            ->willReturn(OutputInterface::VERBOSITY_QUIET);
        $outputInterface->write(Argument::any())
            ->shouldBeCalled();
        $outputInterface->writeln(Argument::any(), 1)
            ->shouldBeCalled();
        $outputInterface->isDecorated()
            ->shouldBeCalled()
            ->willReturn(false);

        return $outputInterface;
    }
}

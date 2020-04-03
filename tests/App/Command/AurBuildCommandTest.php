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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AurBuildCommandTest extends TestCase
{
    public function testDefinitionAndArguments(): void
    {
        $aurCommand = new AurBuildCommand(
            $this->prophesize(AurService::class)->reveal(),
            $this->prophesize(ArchiveService::class)->reveal(),
            $this->prophesize(DockerService::class)->reveal()
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
            $this->prophesize(AurService::class)->reveal(),
            $this->prophesize(ArchiveService::class)->reveal(),
            $this->prophesize(DockerService::class)->reveal()
        );

        $this->assertEquals(
            1,
            $executeMethod->invokeArgs(
                $aurCommand,
                [
                    $this->prophesize(InputInterface::class)->reveal(),
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

        $aurService = $this->prophesize(AurService::class);
        $aurService
            ->getPackageInformation(Argument::exact('chindit'))
            ->shouldBeCalledOnce()
            ->willThrow(PackageNotFoundException::class);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $this->prophesize(ArchiveService::class)->reveal(),
            $this->prophesize(DockerService::class)->reveal()
        );

        $inputInterface = $this->prophesize(InputInterface::class);
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

        $aurService = $this->prophesize(AurService::class);
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

        $archiveService = $this->prophesize(ArchiveService::class);
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
            $this->prophesize(DockerService::class)->reveal()
        );

        $inputInterface = $this->prophesize(InputInterface::class);
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

        $aurService = $this->prophesize(AurService::class);
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

        $archiveService = $this->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');

        $dockerService = $this->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker(Argument::exact('/path/to/chindit/package'))
            ->shouldBeCalledOnce()
            ->willThrow(FileSystemException::class);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal()
        );

        $inputInterface = $this->prophesize(InputInterface::class);
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

        $aurService = $this->prophesize(AurService::class);
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

        $archiveService = $this->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');

        $dockerService = $this->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker(Argument::exact('/path/to/chindit/package'))
            ->shouldBeCalledOnce();
        $dockerService
            ->buildPackage(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal()
        );

        $inputInterface = $this->prophesize(InputInterface::class);
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

    public function testBuildPackageSuccess(): void
    {
        $aurCommandClass = new \ReflectionClass(AurBuildCommand::class);
        $executeMethod = $aurCommandClass->getMethod('execute');
        $executeMethod->setAccessible(true);

        $aurService = $this->prophesize(AurService::class);
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

        $archiveService = $this->prophesize(ArchiveService::class);
        $archiveService
            ->getBuildInformation(
                Argument::exact('https://github.com/chindit/AurBuilder'),
                Argument::exact('chindit')
            )
            ->shouldBeCalledOnce()
            ->willReturn('/path/to/chindit/package');

        $dockerService = $this->prophesize(DockerService::class);
        $dockerService
            ->prepareDocker(Argument::exact('/path/to/chindit/package'))
            ->shouldBeCalledOnce();
        $dockerService
            ->buildPackage(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $aurCommand = new AurBuildCommand(
            $aurService->reveal(),
            $archiveService->reveal(),
            $dockerService->reveal()
        );

        $inputInterface = $this->prophesize(InputInterface::class);
        $inputInterface
            ->getArgument(Argument::exact('package'))
            ->shouldBeCalledOnce()
            ->willReturn('chindit');

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

    private function getOutputInterface(): ObjectProphecy
    {
        $outputFormatter = $this->prophesize(OutputFormatterInterface::class);
        $outputFormatter->format(Argument::any())
            ->willReturnArgument();
        $outputFormatter->isDecorated()
            ->willReturn(false);
        $outputFormatter->setDecorated(Argument::exact(false))
            ->shouldBeCalled();

        $outputInterface = $this->prophesize(OutputInterface::class);
        $outputInterface->getFormatter()
            ->shouldBeCalledTimes(3)
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

<?php

namespace App\Tests\App\Command;

use App\Command\AurBuildCommand;
use App\Command\AurCheckUpdatesCommand;
use App\Repository\PackageRepository;
use App\Repository\PackageRequestRepository;
use App\Service\ArchiveService;
use App\Service\AurService;
use App\Service\DockerService;
use App\Service\RepositoryService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AurCheckUpdatesCommandTest extends TestCase
{
    public function testDefinitionAndArguments(): void
    {
        $aurCommand = new AurCheckUpdatesCommand(
            $this->prophet->prophesize(AurService::class)->reveal(),
            $this->prophet->prophesize(PackageRepository::class)->reveal(),
            $this->prophet->prophesize(PackageRequestRepository::class)->reveal()
        );

        $this->assertEquals('aur:check-updates', $aurCommand->getName());
        $this->assertEquals(
            'Check if registered packages needs an update and update them if asked',
            $aurCommand->getDescription()
        );
        $this->assertCount(1, $aurCommand->getDefinition()->getOptions());
        $this->assertTrue($aurCommand->getDefinition()->hasOption('update'));
        $this->assertEquals(
            new InputOption('update', '-u', InputOption::VALUE_OPTIONAL, 'Update listed packages', false),
            $aurCommand->getDefinition()->getOption('update')
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

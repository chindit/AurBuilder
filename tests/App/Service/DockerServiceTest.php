<?php

namespace App\Tests\App\Service;

use App\Exception\FileSystemException;
use App\Service\DockerService;
use App\Tests\AbstractProphetTest;
use Prophecy\Argument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DockerServiceTest extends AbstractProphetTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!is_dir('/tmp/t1build')) {
            mkdir('/tmp/t1build');
        }

        touch('/tmp/t1build/PKGBUILD');
        touch('/tmp/t1build/test.tar.xz');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unlink('/tmp/t1build/PKGBUILD');
        unlink('/tmp/t1build/test.tar.xz');
        if (is_file('/tmp/t1build/build.sh')) {
            unlink('/tmp/t1build/build.sh');
        }

        rmdir('/tmp/t1build');
    }

    public function testCanHandleBuildDirectoryCreationFailure(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Unable to create build directory');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem->exists(Argument::exact('/chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $fileSystem->mkdir(Argument::exact('/chindit'), Argument::exact(0744))
            ->shouldBeCalledOnce()
            ->willThrow(new IOException('TEST - Unable to create directory'));

        $dockerService = new DockerService('/chindit', '', '', $fileSystem->reveal());
        $dockerService->prepareDocker('');
    }

    public function testEnsureBuildDirectoryIsWritable(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Build directory must be writable');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem->exists(Argument::exact('/chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->exists(Argument::exact('/chindit'))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $dockerService = new DockerService('/chindit', '', '', $fileSystem->reveal());
        $dockerService->prepareDocker();
    }

    public function testCanHandleFailOnBuildFilesCopy(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Unable to copy build files');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem->exists(Argument::exact('/tmp'))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->copy(Argument::exact('/PKGBUILD'), Argument::exact('/tmp/PKGBUILD'))
            ->shouldBeCalledOnce()
            ->willThrow(new IOException('Unable to copy'));

        $dockerService = new DockerService('/tmp', '', '', $fileSystem->reveal());
        $dockerService->prepareDocker();
    }

    public function testCanChangeChmodOnBuildFiles(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Unable to copy build files');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem->exists(Argument::exact('/tmp'))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->copy(Argument::exact('/PKGBUILD'), Argument::exact('/tmp/PKGBUILD'))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->chmod(Argument::exact('/tmp/PKGBUILD'), Argument::exact(0744))
            ->shouldBeCalledOnce()
            ->willThrow(new IOException('Unable to copy'));

        $dockerService = new DockerService('/tmp', '', '', $fileSystem->reveal());
        $dockerService->prepareDocker('');
    }

    public function testCanHandleFailOnBuildScriptCopy(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Unable to copy build files');

        $fileSystem = $this->prophet->prophesize(Filesystem::class);
        $fileSystem->exists(Argument::exact('/tmp'))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->copy(Argument::exact('/PKGBUILD'), Argument::exact('/tmp/PKGBUILD'))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->chmod(Argument::exact('/tmp/PKGBUILD'), Argument::exact(0744))
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $fileSystem->copy(Argument::exact('Resources/dockerTest.sh'), Argument::exact('/tmp/build.sh'))
            ->shouldBeCalledOnce()
            ->willThrow(new IOException('Unable to copy'));

        $dockerService = new DockerService(
            '/tmp',
            __DIR__ . '/../../Resources/dockerTest.sh',
            '',
            $fileSystem->reveal()
        );
        $dockerService->prepareDocker('');
    }

    public function testCanCopyRealFilesToRealDirectories(): void
    {
        $dockerService = new DockerService(
            '/tmp/t1build',
            __DIR__ . '/../../Resources/dockerTest.sh',
            '',
            new Filesystem());
        $dockerService->prepareDocker('/tmp/t1build');

        $this->assertFileExists('/tmp/t1build/PKGBUILD');
    }

    public function testBuildPackage(): void
    {
        $io = $this->prophet->prophesize(SymfonyStyle::class);
        $io->writeln(Argument::exact('out:/'))
            ->shouldBeCalledOnce();

        $dockerService = new DockerService(
            '/tmp/t1build',
            __DIR__ . '/../../Resources/dockerTest.sh',
            'cd / && pwd',
            new Filesystem());
        $dockerService->prepareDocker();

        $this->assertTrue($dockerService->buildPackage($io->reveal()));
    }
}

<?php

namespace App\Tests\App\Service;

use App\Exception\InvalidPackageException;
use App\Service\ArchiveService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ArchiveServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(sys_get_temp_dir() . '/chindit')) {
            $fileSystem->remove(sys_get_temp_dir() . '/chindit');
        }

        if ($fileSystem->exists(sys_get_temp_dir() . '/yay')) {
            $fileSystem->remove(sys_get_temp_dir() . '/yay');
        }

        parent::tearDown();
    }

    public function testCanHandleInvalidApiResponse(): void
    {
        $this->expectException(InvalidPackageException::class);
        $this->expectExceptionMessage('Unable to download and save package «chindit»');

        $responses = [
            new MockResponse('', ['http_code' => 429]),
        ];

        $httpEngine = new MockHttpClient($responses);
        $filesystem = $this->prophesize(Filesystem::class);
        $fileName = tempnam(sys_get_temp_dir(), '');
        $filesystem->tempnam(Argument::any(), Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($fileName);

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $archiveService->getBuildInformation('/fake/url', 'chindit');
    }

    public function testCanHandleErrorOnTemporaryFile(): void
    {
        $this->expectException(InvalidPackageException::class);
        $this->expectExceptionMessage(
            'Unable to create temporary file.  Error returned is following: Unable to create temporary file'
        );

        $responses = [
            new MockResponse('Lorem ipsum dolor sit amet, consectetur adipiscing elit.
		    Duis posuere tempus nibh non imperdiet.
		    Aenean ligula nisi, dignissim consectetur nulla vitae, egestas commodo orci. Donec.'),
        ];

        $httpEngine = new MockHttpClient($responses);
        $filesystem = $this->prophesize(Filesystem::class);
        $filesystem->tempnam(Argument::any(), Argument::any())
            ->shouldBeCalledOnce()
            ->willThrow(new IOException('Unable to create temporary file'));

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $archiveService->getBuildInformation('/fake/url', 'chindit');
    }

    public function testCanHandleInvalidArchive(): void
    {
        $this->expectException(InvalidPackageException::class);
        $this->expectExceptionMessage('Package «chindit» is not a valid archive or is corrupted');

        $responses = [
            new MockResponse('Lorem ipsum dolor sit amet, consectetur adipiscing elit.
		    Duis posuere tempus nibh non imperdiet.
		    Aenean ligula nisi, dignissim consectetur nulla vitae, egestas commodo orci. Donec.'),
        ];

        $httpEngine = new MockHttpClient($responses);
        $filesystem = $this->prophesize(Filesystem::class);
        $filesystem->exists(Argument::containingString('/tmp/'))->shouldBeCalledTimes(2)->willReturn(true);
        $filesystem->remove(Argument::containingString('/tmp/'))->shouldBeCalledOnce()->willReturn(true);
        $filesystem->tempnam(Argument::any(), Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn(tempnam(sys_get_temp_dir(), ''));

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $archiveService->getBuildInformation('/fake/url', 'chindit');
    }

    public function testCanHandleValidArchiveWithInvalidContent(): void
    {
        $this->expectException(InvalidPackageException::class);
        $this->expectExceptionMessage('Package «chindit» is invalid.  No PKGBUILD found');

        $responses = [
            new MockResponse(file_get_contents(__DIR__ . '/../../Resources/invalid.tar.gz')),
        ];

        $httpEngine = new MockHttpClient($responses);
        $filesystem = $this->prophesize(Filesystem::class);
        $filesystem->exists(Argument::containingString('/tmp/'))->shouldBeCalledTimes(2)->willReturn(true);
        $filesystem->remove(Argument::containingString('/tmp/'))->shouldBeCalledOnce()->willReturn(true);
        $filesystem->tempnam(Argument::any(), Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn((new Filesystem())->tempnam(sys_get_temp_dir(), uniqid('', true)));

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $archiveService->getBuildInformation('/fake/url', 'chindit');
    }

    public function testCanHandleValidArchive(): void
    {
        $responses = [
            new MockResponse(file_get_contents(__DIR__ . '/../../Resources/yay.tar.gz')),
        ];

        $httpEngine = new MockHttpClient($responses);
        $filesystem = $this->prophesize(Filesystem::class);
        $filesystem->exists(Argument::containingString('/tmp/'))->shouldBeCalledTimes(2)->willReturn(true);
        $filesystem->remove(Argument::containingString('/tmp/'))->shouldBeCalledOnce()->willReturn(true);
        $filesystem->tempnam(Argument::any(), Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn((new Filesystem())->tempnam(sys_get_temp_dir(), uniqid('', true)));

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $response = $archiveService->getBuildInformation('/fake/url', 'yay');

        $this->assertTrue((new Filesystem())->exists($response));
        $this->assertTrue((new Filesystem())->exists($response . '/PKGBUILD'));

        $this->assertEquals('# Maintainer: Jguer <joaogg3@gmail.com>
pkgname=yay
pkgver=9.4.6
pkgrel=2
pkgdesc="Yet another yogurt. Pacman wrapper and AUR helper written in go."
arch=(\'i686\' \'x86_64\' \'arm\' \'armv7h\' \'armv6h\' \'aarch64\')
url="https://github.com/Jguer/yay"
license=(\'GPL\')
depends=(
  \'pacman>=5.2\'
  \'sudo\'
  \'git\'
)
makedepends=(
  \'go\'
)
source=("${pkgname}-${pkgver}.tar.gz::https://github.com/Jguer/yay/archive/v${pkgver}.tar.gz")
sha1sums=(\'a3b2dd86fda1cc5998899cd69df2102e57ab8fbc\')

build() {
  export GOPATH="$srcdir"/gopath
  cd "$srcdir/$pkgname-$pkgver"
  EXTRA_GOFLAGS="-modcacherw -gcflags all=-trimpath=${PWD} -asmflags all=-trimpath=${PWD}" \
    LDFLAGS="-linkmode external -extldflags \"${LDFLAGS}\"" \
    make VERSION=$pkgver DESTDIR="$pkgdir" build
}

package() {
  cd "$srcdir/$pkgname-$pkgver"
  make VERSION=$pkgver DESTDIR="$pkgdir" PREFIX=/usr install
}
', file_get_contents($response . '/PKGBUILD'));
    }
}

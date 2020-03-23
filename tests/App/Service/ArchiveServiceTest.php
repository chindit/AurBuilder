<?php

namespace App\Tests\App\Service;

use App\Exception\FileSystemException;
use App\Exception\InvalidPackageException;
use App\Service\ArchiveService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ArchiveServiceTest extends TestCase
{
    public function testCanHandleInvalidApiResponse()
    {
        $this->expectException(InvalidPackageException::class);
        $this->expectExceptionMessage('Unable to download and save package «chindit»');

        $responses = [
            new MockResponse('{}'),
        ];

        $httpEngine = new MockHttpClient($responses);
        $filesystem = $this->prophesize(Filesystem::class);

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $archiveService->prepareBuildFiles('/fake/url', 'chindit');
    }

    public function testCanHandleInvalidArchive()
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

        $archiveService = new ArchiveService($httpEngine, $filesystem->reveal());

        $archiveService->prepareBuildFiles('/fake/url', 'chindit');
    }
}

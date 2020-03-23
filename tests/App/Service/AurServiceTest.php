<?php

namespace App\Tests\App\Service;

use App\Exception\PackageNotFoundException;
use App\Model\PackageInformation;
use App\Service\AurService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AurServiceTest extends TestCase
{
    public function testEmptyResponse()
    {
        $this->expectException(PackageNotFoundException::class);
        $this->expectExceptionMessage('Package chindit haven\'t been found due to a network error.');

        $responses = [
            new MockResponse(null),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $aurService->getPackageInformation('chindit');
    }

    public function testNetworkError()
    {
        $this->expectException(PackageNotFoundException::class);
        $this->expectExceptionMessage('Package chindit haven\'t been found due to a network error.');

        $responses = [
            new MockResponse(null, ['http_code' => 500]),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $aurService->getPackageInformation('chindit');
    }

    public function testPackageNotFound()
    {
        $this->expectException(PackageNotFoundException::class);
        $this->expectExceptionMessage('Package chindit doesn\'t exist.');

        $responses = [
            new MockResponse('{}'),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $aurService->getPackageInformation('chindit');
    }

    public function testValidPackage()
    {
        $responses = [
            new MockResponse('{"version":5,"type":"multiinfo","resultcount":1,"results":[{"ID":702647,"Name":"yay","PackageBaseID":115973,"PackageBase":"yay","Version":"9.4.6-2","Description":"Yet another yogurt. Pacman wrapper and AUR helper written in go.","URL":"https:\/\/github.com\/Jguer\/yay","NumVotes":1000,"Popularity":58.748318,"OutOfDate":null,"Maintainer":"jguer","FirstSubmitted":1475688004,"LastModified":1583019462,"URLPath":"\/cgit\/aur.git\/snapshot\/yay.tar.gz","Depends":["pacman>=5.2","sudo","git"],"MakeDepends":["go"],"License":["GPL"],"Keywords":["arm","AUR","go","helper","pacman","wrapper","x86"]}]}'),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $packageResponse = $aurService->getPackageInformation('yay');
        $expectedPackage = new PackageInformation(
            702647,
            'yay',
            '/cgit/aur.git/snapshot/yay.tar.gz',
            '9.4.6-2',
            1583019462,
            'Yet another yogurt. Pacman wrapper and AUR helper written in go.',
        );

        $this->assertEquals($expectedPackage, $packageResponse);
    }
}

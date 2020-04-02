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
    public function testCanHandleEmptyResponse()
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

    public function testCanHandleNetworkError()
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

    public function testCanHandlePackageNotFound()
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

    public function testCanHandleEmptyResultSet()
    {
        $this->expectException(PackageNotFoundException::class);
        $this->expectExceptionMessage('Package chindit doesn\'t exist.');

        $responses = [
            new MockResponse('{"version":5,"type":"multiinfo","resultcount":1,
            	"results":[]}'),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $aurService->getPackageInformation('chindit');
    }

    public function testReturnValidPackageResponse()
    {
        $responses = [
            new MockResponse(
                '{"version":5,"type":"multiinfo","resultcount":1,
            	"results":[{"ID":702647,"Name":"yay","PackageBaseID":115973,
            	"PackageBase":"yay","Version":"9.4.6-2",
            	"Description":"Yet another yogurt. Pacman wrapper and AUR helper written in go.",
            	"URL":"https:\/\/github.com\/Jguer\/yay","NumVotes":1000,"Popularity":58.748318,
            	"OutOfDate":null,"Maintainer":"jguer","FirstSubmitted":1475688004,"LastModified":1583019462,
            	"URLPath":"\/cgit\/aur.git\/snapshot\/yay.tar.gz","Depends":["pacman>=5.2","sudo","git"],
            	"MakeDepends":["go"],"License":["GPL"],"Keywords":["arm","AUR","go","helper","pacman","wrapper","x86"]}]}'
            ),
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

    public function testSearchWithMultipleResults(): void
    {
        $searchResult = '{"version":5,"type":"search","resultcount":9,"results":[{"ID":544072,"Name":"puyo",
		"PackageBaseID":135895,"PackageBase":"puyo","Version":"1.0-1","Description":"A frontend for pacman and yay.",
		"URL":"https:\/\/github.com\/Appadeia\/puyo","NumVotes":5,"Popularity":0.030615,"OutOfDate":null,
		"Maintainer":null,"FirstSubmitted":1536534050,"LastModified":1536985328,
		"URLPath":"\/cgit\/aur.git\/snapshot\/puyo.tar.gz"},{"ID":650242,"Name":"ffpb","PackageBaseID":145346,
		"PackageBase":"ffpb","Version":"0.2.0-2","Description":"A progress bar for ffmpeg. Yay !",
		"URL":"https:\/\/github.com\/althonos\/ffpb","NumVotes":0,"Popularity":0,"OutOfDate":null,
		"Maintainer":"SleeplessSloth","FirstSubmitted":1569459159,"LastModified":1569514631,
		"URLPath":"\/cgit\/aur.git\/snapshot\/ffpb.tar.gz"}]}';

        $responses = [
            new MockResponse($searchResult),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $result = $aurService->searchPackages('chindit');

        $this->assertEquals(
            [
                new PackageInformation(
                    544072,
                    'puyo',
                    '/cgit/aur.git/snapshot/puyo.tar.gz',
                    '1.0-1',
                    1536985328,
                    'A frontend for pacman and yay.'
                ),
                new PackageInformation(
                    650242,
                    'ffpb',
                    '/cgit/aur.git/snapshot/ffpb.tar.gz',
                    '0.2.0-2',
                    1569514631,
                    'A progress bar for ffmpeg. Yay !'
                )
            ],
            $result
        );
    }

    public function testSearchWithSingleResult(): void
    {
        $responses = [
            new MockResponse('{"version":5,"type":"search","resultcount":1,
		    "results":[{"ID":713102,"Name":"pikaur-git","PackageBaseID":129630,"PackageBase":"pikaur-git",
		    "Version":"1.6.9.1-1",
		    "Description":"AUR helper which asks all questions before installing\/building.Inspired by pacaur,yaourt and yay",
		    "URL":"https:\/\/github.com\/actionless\/pikaur","NumVotes":15,
		    "Popularity":0.287667,"OutOfDate":null,"Maintainer":"actionless","FirstSubmitted":1517379650,
		    "LastModified":1585345629,"URLPath":"\/cgit\/aur.git\/snapshot\/pikaur-git.tar.gz"}]}'),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $result = $aurService->searchPackages('chindit');

        $this->assertEquals(
            [
                new PackageInformation(
                    713102,
                    'pikaur-git',
                    '/cgit/aur.git/snapshot/pikaur-git.tar.gz',
                    '1.6.9.1-1',
                    1585345629,
                    'AUR helper which asks all questions before installing/building.Inspired by pacaur,yaourt and yay'
                )
            ],
            $result
        );
    }

    public function testSearchWithNoResult(): void
    {
        $this->expectException(PackageNotFoundException::class);
        $this->expectExceptionMessage('Package chindit doesn\'t exist.');

        $responses = [
            new MockResponse('{"version":5,"type":"multiinfo","resultcount":1,
            	"results":[]}'),
        ];

        $httpEngine = new MockHttpClient($responses);

        $aurService = new AurService($httpEngine);
        $aurService->searchPackages('chindit');
    }
}

<?php

namespace App\Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PackageControllerTest extends WebTestCase
{
    public function testIndexPageWithUpdates(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertEquals('', $client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content table tbody tr td', 'chindit');
    }

    public function testPackageList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/list');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content table tbody tr td', 'chindit');
        $this->assertSelectorTextSame('html body .content table tbody tr td+td', '0.0.1');
    }

    public function testSearchPageWithoutQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/search');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content h3', 'Search for your package');
    }

    public function testSearchPageWithPackageNotFound(): void
    {
        $client = static::createClient();
        $client->request('POST', '/search', ['package' => 'chindit']);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content h3', 'Search for your package');
        $this->assertSelectorTextSame(
            'html body .content .uk-alert-danger p',
            'Your search didn\'t return any result. Are you sure you haven\'t misspelt the package name ?'
        );
    }

    public function testSearchWithPackagesFound(): void
    {
        $client = static::createClient();
        $crawler = $client->request('POST', '/search', ['package' => 'yay-git']);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content h3', 'Search for your package');
        $this->assertSelectorTextSame('body > div > h3.uk-text-bold', '3 result(s) for «yay-git»');
        $this->assertSelectorExists('body > div > table > tbody > tr:nth-child(1) > td.uk-text-center > a');
        $button = $crawler->filter('body > div > table > tbody > tr:nth-child(1) > td.uk-text-center > a');
        $this->assertNotNull($button->attr('href'));
        $this->assertEquals('http://localhost/suggest/pak-config-yay-git', $button->attr('href'));
    }

    public function testSuggestWithInvalidPackage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/suggest/not-found');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content table tbody tr td', 'chindit');
        $this->assertSelectorExists('body > div > div > p');
        $this->assertSelectorTextSame('body > div > div > p', 'Package «not-found» does not exists.');
    }

    public function testSuggestWithValidPackage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/suggest/yay-git');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextSame('html body .content h1', 'Chindit\'s repository');
        $this->assertSelectorTextSame('html body .content table tbody tr td', 'chindit');
        $this->assertSelectorExists('body > div > div > p');
        $this->assertSelectorTextSame('body > div > div > p', 'Package «yay-git» has been successfully requested.');
    }
}

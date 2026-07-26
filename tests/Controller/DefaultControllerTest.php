<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $this->assertInstanceOf(
            AbstractBrowser::class,
            $client
        );

        // Access the login page
        $crawler = $client->request(
            'GET',
            '/login'
        );

        $this->assertResponseIsSuccessful();

        // Submit the login form with valid credentials
        $form = $crawler
            ->selectButton('Se connecter')
            ->form([
                '_username' => 'Mike',
                '_password' => 'password123',
            ]);

        $client->submit($form);

        // Check that authentication redirects successfully
        $this->assertResponseRedirects();

        // Follow redirect to homepage
        $client->followRedirect();

        // Access homepage as authenticated user
        $crawler = $client->request(
            'GET',
            '/'
        );

        $this->assertResponseIsSuccessful();

        $this->assertInstanceOf(
            Crawler::class,
            $crawler
        );

        // Ensure homepage contains at least one h1 element
        $this->assertGreaterThan(
            0,
            $crawler->filter('h1')->count()
        );
    }
}

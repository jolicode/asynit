<?php

declare(strict_types=1);

namespace Asynit\Tests;

use Asynit\Annotation\ChromeTab;
use Asynit\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class ChromeTest extends TestCase
{
    /**
     * @ChromeTab("first")
     */
    public function testGet()
    {
        /** @var \Asynit\Extension\Chrome\Client $client */
        $client = yield $this->createChromeClient();
        /** @var ResponseInterface $response */
        $response = yield $client->request('https://jolicode.com/');
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = new Crawler((string) $response->getBody());
        $title = $crawler->filter('body > header > div > h1');


        $this->assertStatusCode(200, $response);
        $this->assertContains('Envie de goodies', $title->text());
    }
}

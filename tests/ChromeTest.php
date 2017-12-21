<?php

declare(strict_types=1);

namespace Asynit\Tests;

use Asynit\Annotation\ChromeTab;
use Asynit\TestCase;

class ChromeTest extends TestCase
{
    /**
     * @ChromeTab("first")
     */
    public function testGet()
    {
        /** @var \Asynit\Extension\Chrome\Client $client */
        $client = yield $this->createChromeClient();

        yield $client->request('https://jolicode.com/');
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = yield $client->getCrawler();

        $title = $crawler->filter('body > header > div > h1')->text();

        $this->assertContains('Envie de goodies', $title);
    }
}

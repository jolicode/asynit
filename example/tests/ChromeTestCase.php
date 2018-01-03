<?php

declare(strict_types=1);

class ChromeTestCase extends \Asynit\TestCase
{
    /**
     * @\Asynit\Annotation\ChromeTab("sessionA")
     */
    public function testGet()
    {
        /** @var \Asynit\Extension\Chrome\Client $client */
        $client = yield $this->createChromeClient();
        $response = yield $client->request('https://jolicode.com/');
        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = yield $client->getCrawler();

//        var_dump($crawler);
    }

    /**
     * @\Asynit\Annotation\ChromeTab("sessionA")
     */
    public function testGetA()
    {
        /** @var \Asynit\Extension\Chrome\Client $client */
        $client = yield $this->createChromeClient();
        $response = yield $client->request('https://jolicode.com/');
        $content = yield $client->evaluate('document.documentElement.outerHTML');
    }

    /**
     * @\Asynit\Annotation\ChromeTab("sessionB")
     */
    public function testGetB()
    {
        /** @var \Asynit\Extension\Chrome\Client $client */
        $client = yield $this->createChromeClient();
        $response = yield $client->request('https://jolicode.com/');
        $content = yield $client->evaluate('document.documentElement.outerHTML');
    }
}

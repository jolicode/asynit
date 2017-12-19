<?php

declare(strict_types=1);

class ChromeTestCase extends \Asynit\TestCase
{
    /**
     * @\Asynit\Annotation\ChromeSession("sessionA")
     */
    public function testGet()
    {
        /** @var \Asynit\Extension\Chrome\Page $page */
        $page = yield $this->navigate('https://jolicode.com/');
        $content = yield $page->evaluate('document.documentElement.outerHTML');
    }

    /**
     * @\Asynit\Annotation\ChromeSession("sessionB")
     */
    public function testGetB()
    {
        /** @var \Asynit\Extension\Chrome\Page $page */
        $page = yield $this->navigate('https://jolicode.com/');
        $content = yield $page->evaluate('document.documentElement.outerHTML');
    }
}

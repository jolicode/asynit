<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// @TODO Change me
$url = 'ws://127.0.0.1:9222/devtools/browser/b1c46ece-85c4-48b5-850d-687296d0566e';

\Amp\Loop::run(function () use($url) {
    try {
        $logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL));
        $browser = new \Asynit\Extension\Chrome\Browser($url, $logger);
        $isConnected = yield $browser->connect();

        if ($isConnected) {
            /** @var \Asynit\Extension\Chrome\Session $session */
            $session = yield $browser->createSession();
            /** @var \Asynit\Extension\Chrome\Page $page */
            $page = yield $session->createPage();
            $result = yield $page->navigate('https://jolicode.com/');
            $test = yield $page->evaluate('document.documentElement.innerHTML');
//            $screen = yield $page->getDom();
            var_dump($test);

            $browser->close();
        }
    } catch (\Throwable $e) {

        var_dump($e->getMessage());
        throw $e;
    }
});



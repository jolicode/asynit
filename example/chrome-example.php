<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';


\Amp\Loop::run(function () {
    try {
        $process = new \Amp\Process\Process(
            [
                "google-chrome-stable",
                "--headless",
                "--disable-gpu",
                "--remote-debugging-port=9222",
                "about:blank"
            ]
        );

        $process->start();
        $found = false;
        $url = "";

        while (!$found && $process->isRunning()) {
            $readed = yield $process->getStderr()->read();

            if (preg_match("/DevTools listening on (ws\:\/\/.+?)\n/", $readed, $matches)) {
                $found = true;
                $url = $matches[1];
            }
        }

        $logger = new \Symfony\Component\Console\Logger\ConsoleLogger(
            new \Symfony\Component\Console\Output\ConsoleOutput(
                \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL
            )
        );
        $browser = new \Asynit\Extension\Chrome\Browser($url, $logger);
        $isConnected = yield $browser->connect();

        if ($isConnected) {
            /** @var \Asynit\Extension\Chrome\Session $session */
            $session = yield $browser->createSession();
            /** @var \Asynit\Extension\Chrome\Page $page */
            $page = yield $session->createPage();
            $result = yield $page->navigate('https://jolicode.com/');
            $test = yield $page->evaluate('document.documentElement.outerHTML');

            var_dump($test);
        }

        $process->kill();

        // @TODO Process is not killed :/
        posix_kill($process->getPid(), SIGTERM);
    } catch (\Amp\Websocket\ClosedException $exception) {
        return;
    } catch (\Throwable $e) {

        var_dump($e->getMessage());
        throw $e;
    }
});



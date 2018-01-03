<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(
    new \Symfony\Component\Console\Output\ConsoleOutput(
\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG
    )
);

$browser = new \Asynit\Runner\LazyChromeBrowser('/usr/bin/chromium',null, $logger);

try {
    \Amp\Loop::run(function () use($browser) {
        \Amp\asyncCall(function () use($browser) {
            try {
                /** @var \Asynit\Extension\Chrome\Target $session */
                $session = yield $browser->getSession('test');

                /** @var \Asynit\Extension\Chrome\Tab $page */
                $page = yield $session->createTab();
                yield $page->navigate('https://www.afflelou.com/');
                yield $page->evaluate('document.documentElement.outerHTML');

                yield $page->waitForLoadEvent();

                yield $page->screenshot();

                $image1 = yield $page->screenshot(false, 1700, 4600);
                file_put_contents('test1.png', $image1);
                $image1Obj = new imagick('test1.png');

                // Wait for some ms (animated header)
                yield (new \Amp\Delayed(100, 'yolo'));

                $image2 = yield $page->screenshot(false, 1700, 4600);
                file_put_contents('test2.png', $image2);
                $image2Obj = new imagick('test2.png');

                $result = $image1Obj->compareImages($image2Obj, Imagick::METRIC_PEAKABSOLUTEERROR);
                $compared = $result[0];
                $compared->setImageFormat("png");
                $compared->writeImage('compared.png');
            } catch (\Throwable $e) {
                var_dump($e->getMessage());

                throw $e;
            } finally {
                $browser->shutdown();
            }
        });
    });
} finally {
    $browser->shutdown();
}



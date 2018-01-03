<?php

declare(strict_types=1);

namespace Asynit\Runner;

use function Amp\call;
use function Amp\File\exists;
use Amp\Process\Process;
use Amp\Promise;
use Amp\Sync\LocalMutex;
use Amp\Sync\Lock;
use Amp\Sync\Mutex;
use Asynit\Extension\Chrome\Browser;
use Asynit\Extension\Chrome\Downloader;
use Asynit\Extension\Chrome\Target;
use Psr\Log\NullLogger;

class LazyChromeBrowser
{
    /** @var Process */
    private $process;

    /** @var int */
    private $pid;

    /** @var Browser */
    private $browser;

    /** @var Target[] */
    private $sessions = [];

    /** @var Mutex[] */
    private $mutex = [];

    /** @var Lock[] */
    private $locks = [];

    /** @var Mutex */
    private $loadLock;

    private $logger;

    private $downloader;

    private $executableFilePath;

    public function __construct(string $executableFilePath = null, Downloader $downloader = null, $logger = null)
    {
        $this->downloader = $downloader ?? new Downloader();
        $this->loadLock = new LocalMutex();
        $this->executableFilePath = $executableFilePath;
        $this->logger = $logger ?? new NullLogger();
    }

    protected function load(): Promise
    {
        return call(function () {
            /** @var Lock $lock */
            $lock = yield $this->loadLock->acquire();

            if ($this->browser !== null) {
                return;
            }

            /**
             * This is fucking ugly, we should do the same as pupetter and use an existing binary / docker container
             */
            if ($this->process && $this->process->isRunning()) {
                return;
            }

            if (null === $this->executableFilePath) {
                $this->executableFilePath = yield $this->downloader->getBinaryPath();
            }

            if (!yield exists($this->executableFilePath)) {
                throw new \RuntimeException('Chrome binary not existing for path : ' . $this->executableFilePath);
            }

            $this->process = new Process(
                [
                    $this->executableFilePath,
//                    '--no-sandbox', //@TODO Security issue
                    '--disable-background-networking',
                    '--disable-background-timer-throttling',
                    '--disable-client-side-phishing-detection',
                    '--disable-default-apps',
                    '--disable-extensions',
                    '--disable-hang-monitor',
                    '--disable-popup-blocking',
                    '--disable-prompt-on-repost',
                    '--disable-sync',
                    '--disable-translate',
                    '--metrics-recording-only',
                    '--no-first-run',
                    '--remote-debugging-port=0',
                    '--safebrowsing-disable-auto-update',
                    '--headless',
                    '--disable-gpu',
                    '--hide-scrollbars',
                    '--mute-audio'
                ]
            );

            $this->process->start();
            $url = null;
            $readed = '';

            while (null === $url && $this->process->isRunning()) {
                $readed .= yield $this->process->getStderr()->read();

                if (preg_match("/DevTools listening on (ws\:\/\/.+?)\n/", $readed, $matches)) {
                    $url = $matches[1];
                }
            }

            if (null === $url) {
                $lock->release();

                throw new \RuntimeException('Unable to launch chrome process : ' . $readed);
            }

            $this->pid = yield $this->process->getPid();
            $this->browser = new Browser($url, $this->logger);

            yield $this->browser->connect();

            $lock->release();
        });
    }

    public function getSession($name): Promise
    {
        return call(function () use ($name) {
            if (!array_key_exists($name, $this->mutex)) {
                $this->mutex[$name] = new LocalMutex();
            }

            $this->locks[$name] = yield $this->mutex[$name]->acquire();

            if ($this->browser === null) {
                yield $this->load();
            }

            if (!array_key_exists($name, $this->sessions)) {
                $this->sessions[$name] = yield $this->browser->createTarget();
            }

            return $this->sessions[$name];
        });
    }

    public function releaseSession($name)
    {
        if (!array_key_exists($name, $this->locks)) {
            return;
        }

        $this->locks[$name]->release();
    }

    public function shutdown()
    {
        if ($this->pid) {
            \posix_kill($this->pid, SIGTERM);
            $this->browser = null;
            $this->process = null;
            $this->pid = null;
            $this->sessions = [];
            $this->locks = [];
            $this->mutex = [];
        }
    }
}

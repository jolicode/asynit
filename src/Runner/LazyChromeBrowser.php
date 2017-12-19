<?php

declare(strict_types=1);

namespace Asynit\Runner;

use function Amp\call;
use Amp\Process\Process;
use Amp\Promise;
use Amp\Sync\LocalMutex;
use Amp\Sync\Lock;
use Amp\Sync\Mutex;
use Asynit\Extension\Chrome\Browser;
use Asynit\Extension\Chrome\Session;
use Psr\Log\NullLogger;

class LazyChromeBrowser
{
    /** @var Process */
    private $process;

    /** @var int */
    private $pid;

    /** @var Browser */
    private $browser;

    /** @var Session[] */
    private $sessions = [];

    /** @var Mutex[] */
    private $mutex = [];

    /** @var Lock[] */
    private $locks = [];

    /** @var Mutex */
    private $loadLock;

    public function __construct()
    {
        $this->loadLock = new LocalMutex();
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

            $this->process = new Process(
                [
                    "exec",
                    "google-chrome-stable",
                    "--disable-gpu",
                    "--remote-debugging-port=9222",
                    "about:blank"
                ]
            );

            $this->process->start();
            $found = false;
            $url = "";

            while (!$found && $this->process->isRunning()) {
                $readed = yield $this->process->getStderr()->read();

                if (preg_match("/DevTools listening on (ws\:\/\/.+?)\n/", $readed, $matches)) {
                    $found = true;
                    $url = $matches[1];
                }
            }


            $this->pid = yield $this->process->getPid();
            $this->browser = new Browser($url, new NullLogger());

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
                $this->sessions[$name] = yield $this->browser->createSession();
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
        }
    }
}

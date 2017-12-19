<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Promise;

trait ChromeTestCaseTrait
{
    protected $session;

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    public function getSession(): Session
    {
        if ($this->session === null) {
            throw new \RuntimeException('No session available, please use the according annotation to have it');
        }

        return $this->session;
    }

    public function navigate($uri): Promise
    {
        return call(function () use($uri) {
            $session = $this->getSession();
            /** @var page $page */
            $page = yield $session->createPage();

            yield $page->navigate($uri);

            return $page;
        });
    }
}

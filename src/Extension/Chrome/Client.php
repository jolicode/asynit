<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Promise;
use Symfony\Component\DomCrawler\Crawler;

class Client
{
    private $tab;

    public function __construct(Tab $tab)
    {
        $this->tab = $tab;
    }

    public function request($url): Promise
    {
        return $this->tab->navigate($url);
    }

    public function getCrawler(): Promise
    {
        return call(function () {
            $evaluateData = yield $this->evaluate('document.documentElement.outerHTML');

            if (array_key_exists('result', $evaluateData) && array_key_exists('value', $evaluateData['result'])) {
                return new Crawler($evaluateData['result']['value']);
            }

            throw new \RuntimeException('Cannot get crawler for the current page');
        });
    }

    public function screenshot($fullPage = true, $width = 1920, $height = 1080): Promise
    {
        return $this->tab->screenshot($fullPage, $width, $height);
    }

    public function getHistory()
    {
    }

    public function click()
    {
    }

    public function submit()
    {
    }

    public function back()
    {
    }

    public function forward()
    {
    }

    public function reload()
    {
    }

    public function evaluate($expression): Promise
    {
        return $this->tab->evaluate($expression);
    }
}

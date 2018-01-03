<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Promise;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\ResponseFactory;
use Symfony\Component\DomCrawler\Crawler;

class Client
{
    private $tab;

    public function __construct(Tab $tab, ResponseFactory $responseFactory = null)
    {
        $this->tab = $tab;
        $this->responseFactory = $responseFactory ?? new GuzzleMessageFactory();
    }

    public function request($url): Promise
    {
        return call(function () use ($url) {
            $body = null;

            $responseData = yield $this->tab->navigate($url);
            $evaluateData = yield $this->evaluate('document.documentElement.outerHTML');

            if (array_key_exists('result', $evaluateData) && array_key_exists('value', $evaluateData['result'])) {
                $body = $evaluateData['result']['value'];
            }

            $response = $this->responseFactory->createResponse(
                $responseData['status'],
                $responseData['statusText'],
                $responseData['headers'],
                $body,
                $responseData['protocol']
            );

            return $response;
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

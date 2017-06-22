<?php

declare(strict_types=1);

namespace Asynit;

use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class SmokerTestCase extends TestCase
{
    public function smokeTest($data)
    {
        list($uri, $expected) = $data;

        $this->get($uri)->shouldResolve(function (ResponseInterface $response) use ($expected) {
            Assert::eq($response->getStatusCode(), $expected['status']);
        });
    }
}

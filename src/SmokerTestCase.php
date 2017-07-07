<?php

declare(strict_types=1);

namespace Asynit;

use Psr\Http\Message\ResponseInterface;

class SmokerTestCase extends TestCase
{
    public function smokeTest($data)
    {
        list($uri, $expected) = $data;

        $this->get($uri)->shouldResolve(function (ResponseInterface $response) use ($expected) {
            static::assertStatusCode($expected['status'], $response);
        });
    }
}

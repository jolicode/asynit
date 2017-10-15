<?php

declare(strict_types=1);

namespace Asynit;

class SmokerTestCase extends TestCase
{
    public function smokeTest($data)
    {
        list($uri, $expected) = $data;

        $response = yield $this->get($uri);

        static::assertStatusCode($expected['status'], $response);
    }
}

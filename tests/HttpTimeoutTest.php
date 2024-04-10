<?php

namespace Asynit\Tests;

use Asynit\Attribute\HttpClientConfiguration;
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\HttpClientWebCaseTrait;

#[TestCase]
#[HttpClientConfiguration(0.01)]
class HttpTimeoutTest
{
    use HttpClientWebCaseTrait;

    public function testTimeout()
    {
        try {
            $this->get('http://127.0.0.1:8081/delay/1');
            $hasException = false;
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Amp\Http\Client\HttpException::class, $e);
            $hasException = true;
        }

        $this->assertTrue($hasException);
    }
}

<?php

namespace Asynit\Tests;

use Asynit\Annotation\Depend;
use Asynit\TestCase;
use Http\Client\Exception;
use Psr\Http\Message\ResponseInterface;

class FunctionalTests extends TestCase
{
    public function testReturn()
    {
        return 'tata';
    }

    public function testGet()
    {
        $response = yield $this->get('http://httpbin.org');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStatusCode(200, $response);

        return 'foo';
    }

    public function testError()
    {
        $exception = null;

        try {
            $response = yield $this->get('http://something-is-not-reachable');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'Not null exception');
    }

    /**
     * @Depend("testGet")
     */
    public function testDepend($value)
    {
        $this->assertEquals('foo', $value);
    }

    public function testStartParallel()
    {
        return time();
    }

    public function testParallel1()
    {
        yield $this->get('http://httpbin.org/delay/7');
    }

    public function testParallel2()
    {
        yield $this->get('http://httpbin.org/delay/7');
    }

    public function testParallel3()
    {
        yield $this->get('http://httpbin.org/delay/7');
    }

    public function testParallel4()
    {
        yield $this->get('http://httpbin.org/delay/7');
    }

    /**
     * @Depend("testStartParallel")
     * @Depend("testParallel1")
     * @Depend("testParallel2")
     * @Depend("testParallel3")
     * @Depend("testParallel4")
     */
    public function testEndParallel($start)
    {
        $end = time();

        $this->assertLessThan(10, $end - $start);
    }
}

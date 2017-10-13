<?php

class HttpbinTest extends \Asynit\TestCase
{
    public function testReturn()
    {
        return 'tata';
    }

    public function testGet()
    {
        $server = null;

        $response = yield $this->get('http://httpbin.org');

        return $response->getHeaderLine('Server');
    }

    public function testFoo()
    {
        yield $this->get('http://httpbin.org/delay/3');
    }

    public function testFoo2()
    {
        yield $this->get('http://httpbin.org/delay/3');
    }

    public function testFoo3()
    {
        yield $this->get('http://httpbin.org/delay/2');
    }

    public function testFoo4()
    {
        yield $this->get('http://httpbin.org/delay/7');
    }

    public function testFoo5()
    {
        yield $this->get('http://httpbin.org/delay/1');
    }

    public function testFoo6()
    {
        yield $this->get('http://httpbin.org/delay/1');
    }

    public function testFoo7()
    {
        yield $this->get('http://httpbin.org/delay/1');
    }

    public function testFoo8()
    {
        yield $this->get('http://httpbin.org/delay/1');
    }

    public function testFoo9()
    {
        $promises = [];
        $promises[] = $this->get('http://httpbin.org/delay/1');
        $promises[] = $this->get('http://httpbin.org/delay/1');
        $promises[] = $this->get('http://httpbin.org/delay/1');
        $promises[] = $this->get('http://httpbin.org/delay/1');
        $promises[] = $this->get('http://httpbin.org/delay/1');

        yield \Amp\Promise\all($promises);
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     */
    public function testDummy($token)
    {
        yield $this->get('http://httpbin.org');

        self::assertEquals('foo', $token);

        yield $this->get('http://httpbin.org');

        self::assertEquals('meinheld/0.6.1', $token);
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     */
    public function testDummy1($token)
    {
        self::assertEquals('meinheld/0.6.1', $token);

        $this->get('http://httpbin.org');
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     * @\Asynit\Annotation\Depend("testReturn")
     */
    public function testDummy2($token, $return)
    {
        self::assertEquals('tata', $return);
        self::assertEquals('meinheld/0.6.1', $token);
    }
}

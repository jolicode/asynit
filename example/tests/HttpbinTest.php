<?php

class HttpbinTest extends \Asynit\TestCase
{
    /**
     * @\Asynit\Annotation\DisplayName("Test a super display name")
     */
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
        $response = yield $this->get('http://httpbin.org/delay/3');

        $this->assertStatusCode(200, $response);
    }

    public function testFoo2()
    {
        yield $this->get('http://httpbin.org/delay/3');
    }

    public function testFoo3()
    {
        yield $this->get('http://httpbin.org/delay/2');
    }

//    public function testFoo4()
//    {
//        yield $this->get('http://httpbin.org/delay/7');
//    }

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

        yield $promises;
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     */
    public function testDummy($token)
    {
        yield $this->get('http://httpbin.org');

        $this->assertEquals('foo', $token);

        yield $this->get('http://httpbin.org');

        $this->assertEquals('meinheld/0.6.1', $token);
    }

    /**
     * @\Asynit\Annotation\Depend("testDummy")
     */
    public function testIgnored()
    {
        throw new \Exception();
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     * @\Asynit\Annotation\Depend("testFoo")
     */
    public function testDummy1($token)
    {
        $this->assertEquals('meinheld/0.6.1', $token);

        $this->get('http://httpbin.org');
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     * @\Asynit\Annotation\Depend("testReturn")
     */
    public function testDummy2($token, $return)
    {
        $this->assertEquals('tata', $return);
        $this->assertEquals('meinheld/0.6.1', $token);
    }
}

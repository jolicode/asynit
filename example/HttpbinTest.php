<?php

class HttpbinTest extends \Asynit\TestCase
{
    public function testFoo()
    {
        $this->get('http://httpbin.org/delay/3')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo2()
    {
        $this->get('http://httpbin.org/delay/3')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo3()
    {
        $this->get('http://httpbin.org/delay/2')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo4()
    {
        $this->get('http://httpbin.org/delay/7')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo5()
    {
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo6()
    {
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo7()
    {
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    public function testFoo8()
    {
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     */
    public function testDummy($token)
    {
        \Assert\Assertion::eq('toto', $token);

        $this->get('http://httpbin.org')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     */
    public function testDummy1($token)
    {
        \Assert\Assertion::eq('nginx', $token);

        $this->get('http://httpbin.org')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
    }

    /**
     * @\Asynit\Annotation\Depend("testGet")
     */
    public function testDummy2($token)
    {
        \Assert\Assertion::eq('toto', $token);
    }

    public function &testGet()
    {
        $server = null;

        $this->get('http://httpbin.org')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) use (&$server) {
                $server = $response->getHeaderLine('Server');
                var_dump($server);
            }
        );

        return $server;
    }
}

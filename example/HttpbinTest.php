<?php

class HttpbinTest extends \Asynit\TestCase
{
    public function testReturn()
    {
        return 'tata';
    }

    public function &testGet()
    {
        $server = null;

        $this->get('http://httpbin.org')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) use (&$server) {
                $server = $response->getHeaderLine('Server');
            }
        );


        return $server;
    }

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

    public function testFoo9()
    {
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
        $this->get('http://httpbin.org/delay/1')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) {
            }
        );
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
        $this->get('http://httpbin.org')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response) use ($token) {
                \Assert\Assertion::eq('toto', $token);
            }
        );

        $this->get('http://httpbin.org')->shouldResolve(
            function (\Psr\Http\Message\ResponseInterface $response)  use ($token) {
                \Assert\Assertion::eq('nginx', $token);
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
     * @\Asynit\Annotation\Depend("testReturn")
     */
    public function testDummy2($token, $return)
    {
        \Assert\Assertion::eq('tata', $return);
        \Assert\Assertion::eq('nginx', $token);
    }
}

<?php

namespace Asynit\Runner;

use Amp\Future;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Psr7\PsrAdapter;
use Amp\Http\Client\Psr7\PsrHttpClient;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Asynit\Pool;
use Asynit\Test;
use Asynit\TestCase;
use Asynit\TestWorkflow;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function Amp\coroutine;

class PoolRunner
{
    private Semaphore $semaphore;

    private ClientInterface $client;

    public function __construct(private RequestFactoryInterface $requestFactory, private ResponseFactoryInterface $responseFactory, private StreamFactoryInterface $streamFactory,  private TestWorkflow $workflow, $concurrency = 10, private bool $allowSelfSignedCertificate = false)
    {
        $this->semaphore = new LocalSemaphore($concurrency);

        $tlsContext = new ClientTlsContext('');

        if ($allowSelfSignedCertificate) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));
        $client = $builder->build();
        $adapter = new PsrAdapter($requestFactory, $responseFactory);
        $this->client = new PsrHttpClient($client, $adapter);
    }

    public function loop(Pool $pool)
    {
        ob_start();
        /** @var Future[] $futures */
        $futures = [];

        while (!$pool->isEmpty()) {
            $test = $pool->getTestToRun();

            if (null === $test) {
                Future\any($futures);

                continue;
            }

            $this->workflow->markTestAsRunning($test);

            $futures[$test->getIdentifier()] = coroutine(function () use($test, &$futures) {
                $this->run($test);

                unset($futures[$test->getIdentifier()]);
            });
        }
        ob_end_flush();
    }

    protected function run(Test $test)
    {
        try {
            $testCase = $this->buildTestCase($test);
            $testCase->setUp($this->client);

            $method = $test->getMethod()->getName();
            $args = $test->getArguments();

            set_error_handler(__CLASS__.'::handleInternalError');

            try {
                $result = $testCase->$method(...$args);
            } finally {
                restore_error_handler();
            }

            foreach ($test->getChildren() as $childTest) {
                $childTest->addArgument($result, $test);
            }

            $this->workflow->markTestAsSuccess($test);
        } catch (\Throwable $error) {
            $this->workflow->markTestAsFailed($test, $error);
        }
    }

    private function buildTestCase(Test $test): TestCase
    {
        return $test->getMethod()->getDeclaringClass()->newInstance($this->requestFactory, $this->streamFactory, $this->semaphore, $test, $this->client);
    }

    public static function handleInternalError($type, $message, $file, $line)
    {
        $message = "$message in $file:$line";

        throw new \ErrorException($message, 0, $type, $file, $line);
    }
}

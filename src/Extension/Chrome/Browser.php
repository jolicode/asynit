<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use Amp\Promise;
use function Amp\Websocket\connect;
use Amp\Websocket\Connection;
use Amp\Websocket\Message;
use Amp\Deferred;
use Psr\Log\LoggerInterface;

class Browser extends EventEmitter
{
    private $endpoint;

    /** @var Connection */
    private $connection;

    private $nextMessageId = 0;

    /** @var Deferred[] */
    private $registry = [];

    /** @var Session[] */
    private $sessions = [];

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $endpoint, LoggerInterface $logger)
    {
        $this->endpoint = $endpoint;
        $this->logger = $logger;
    }

    public function connect()
    {
        return \Amp\call(function () {
            try {
                $this->connection = yield connect($this->endpoint);
                $this->loop();

                return true;
            } catch (\Throwable $exception) {
                return false;
            }
        });
    }

    protected function loop()
    {
        return \Amp\call(function () {
            $error = false;

            while ($this->connection !== null && $this->connection->valid()) {
                /** @var Message $message */
                $message = $this->connection->current();
                $data = yield $message->read();
                $this->logger->info('Receive message ' . $data);
                $decoded = @json_decode($data, true);

                if ($decoded !== false && isset($decoded['id'])) {
                    $messageId = $decoded['id'] ?? null;

                    if (array_key_exists($messageId, $this->registry)) {
                        $deferred = $this->registry[$messageId];
                        unset($this->registry[$messageId]);

                        $deferred->resolve($decoded['result']);
                    }
                }

                if (array_key_exists('method', $decoded)) {
                    $method = $decoded['method'];
                    $params = $decoded['params'];

                    $this->emit($method, $params);
                }

                $this->connection->next();
            }

            return $error;
        });
    }

    public function close()
    {
        $this->connection->close();
    }

    protected function isConnected()
    {
        return $this->connection !== null;
    }

    public function send(string $method, array $params = []): Promise
    {
        $messageId = $this->nextMessageId;
        $deferred = new Deferred();
        $this->registry[$messageId] = $deferred;

        \Amp\call(function () use($method, $params, $messageId) {
            // Force {} for encoding
            if (empty($params)) {
                $params = new \stdClass();
            }

            $message = json_encode([
                'id' => $messageId,
                'method' => $method,
                'params' => $params
            ]);

            $this->nextMessageId++;

            $this->logger->info('Send message ' . $message);

            yield $this->connection->send($message);
        });

        return $deferred->promise();
    }

    public function createSession(): Promise
    {
        return \Amp\call(function () {
            $targetData = yield $this->send('Target.createTarget', [
                'url' => 'about:blank'
            ]);

            $targetId = $targetData['targetId'];
            $sessionData = yield $this->send('Target.attachToTarget', [
                'targetId' => $targetId
            ]);

            $session = new Session($this, $targetId, $sessionData['sessionId'], $this->logger);

            $this->sessions[$sessionData['sessionId']] = $session;

            return $session;
        });
    }
}

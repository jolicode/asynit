<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use Amp\Promise;
use Amp\Websocket\ClosedException;
use function Amp\Websocket\connect;
use Amp\Websocket\Connection;
use Amp\Websocket\Message;
use Amp\Deferred;
use Amp\Websocket\Options;
use Psr\Log\LoggerInterface;

class Browser extends EventEmitter
{
    private $endpoint;

    /** @var Connection */
    private $connection;

    private $nextMessageId = 0;

    /** @var Deferred[] */
    private $registry = [];

    /** @var Target[] */
    private $targets = [];

    /** @var LoggerInterface */
    private $logger;

    private $options;

    public function __construct(string $endpoint, LoggerInterface $logger)
    {
        $this->endpoint = $endpoint;
        $this->logger = $logger;
        $this->options = (new Options())
            ->withMaximumMessageSize(32 * 1024 * 1024) // 32MB
            ->withMaximumFrameSize(32 * 1024 * 1024) // 32MB
            ->withValidateUtf8(true);
    }

    public function connect()
    {
        return \Amp\call(function () {
            $this->connection = yield connect($this->endpoint, null, null, $this->options);
            $this->loop();
        });
    }

    protected function loop()
    {
        return \Amp\asyncCall(function () {
            try {
                while ($message = yield $this->connection->receive()) {
                    /** @var Message $message */
                    $data = yield $message->buffer();
                    $this->logger->info('Receive message ' . $data);
                    $decoded = @json_decode($data, true);

                    if (!$decoded) {
                        $this->logger->error('Cannot decode message ' . $data);
                    }

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
                }
            } catch (ClosedException $exception) {
            }
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

    public function createTarget(): Promise
    {
        return \Amp\call(function () {
            $targetData = yield $this->send('Target.createTarget', [
                'url' => 'about:blank'
            ]);

            $targetId = $targetData['targetId'];
            $sessionData = yield $this->send('Target.attachToTarget', [
                'targetId' => $targetId
            ]);

            $target = new Target($this, $targetId, $sessionData['sessionId'], $this->logger);

            $this->targets[$sessionData['sessionId']] = $target;

            return $target;
        });
    }
}

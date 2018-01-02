<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use Amp\Deferred;
use Amp\Promise;
use Psr\Log\LoggerInterface;

/**
 * Internal class that allows to communicate with a specific target (tab)
 */
class Target extends EventEmitter
{
    private $browser;

    private $targetId;

    private $sessionId;

    private $nextMessageId = 0;

    private $logger;

    /** @var Deferred[] */
    private $registry = [];

    public function __construct(Browser $browser, string $targetId, string $sessionId, LoggerInterface $logger)
    {
        $this->browser = $browser;
        $this->targetId = $targetId;
        $this->sessionId = $sessionId;
        $this->logger = $logger;

        $this->browser->on('Target.receivedMessageFromTarget', function ($data) {
            $sessionId = $data['sessionId'];

            if ($sessionId === $this->sessionId) {
                $message = @json_decode($data['message'], true);

                if (array_key_exists('id', $message) && array_key_exists($message['id'], $this->registry)) {
                    $deferred = $this->registry[$message['id']];
                    unset($this->registry[$message['id']]);

                    if (isset($message['result'])) {
                        $deferred->resolve($message['result']);
                    } elseif (array_key_exists('error', $message)) {
                        // Should fail with an error
                        $deferred->fail(new \RuntimeException($message['error']['message'], $message['error']['code']));
                    }
                }

                if (array_key_exists('method', $message)) {
                    $this->emit($message['method'], $message['params']);
                }
            }
        });
    }

    public function send(string $method, array $params = []): Promise
    {
        $messageId = $this->nextMessageId;
        $this->nextMessageId++;

        if (empty($params)) {
            $params = new \stdClass();
        }

        $message = json_encode([
            'id' => $messageId,
            'method' => $method,
            'params' => $params,
        ]);

        $deffered = new Deferred();
        $this->registry[$messageId] = $deffered;

        $this->browser->send('Target.sendMessageToTarget', [
            'sessionId' => $this->sessionId,
            'message' => $message,
        ]);

        return $deffered->promise();
    }

    public function createTab(): Promise
    {
        return \Amp\call(function () {
            yield $this->send('Page.enable');
            yield [
                $this->send('Network.enable'),
                $this->send('Runtime.enable'),
                $this->send('Security.enable'),
                $this->send('DOM.enable'),
                $this->send('Performance.enable'),
            ];

            yield $this->send('Security.setOverrideCertificateErrors', ['override' => true]);

            try {
                $frameTree = yield $this->send('Page.getFrameTree');
            } catch (\Throwable $e) {
                $frameTree = yield $this->send('Page.getResourceTree');
            }

            $tab = new Tab($this, $frameTree['frameTree']);

            // @TODO Need to allow user to set a custom default viewport
            yield $tab->setViewport(1600, 1200);

            return $tab;
        });
    }
}

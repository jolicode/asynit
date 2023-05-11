<?php

namespace Asynit\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @implements \ArrayAccess<string, mixed>
 */
class ApiResponse implements \ArrayAccess, ResponseInterface
{
    /**
     * @var array<string, mixed>|null
     */
    private array|null $data = null;

    public function __construct(private ResponseInterface $response)
    {
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    private function ensureBodyIsRead(bool $associative = true): void
    {
        if (null === $this->data) {
            $this->data = json_decode($this->response->getBody(), $associative, flags: JSON_THROW_ON_ERROR);
        }
    }

    public function json(bool $associative = true): mixed
    {
        $this->ensureBodyIsRead($associative);

        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->ensureBodyIsRead();

        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->ensureBodyIsRead();

        if (!isset($this->data[$offset])) {
            throw new \InvalidArgumentException(sprintf('The key "%s" does not exist in the response.', $offset));
        }

        return $this->data[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->ensureBodyIsRead();

        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->ensureBodyIsRead();

        unset($this->data[$offset]);
    }

    public function getProtocolVersion()
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): self
    {
        return new self($this->response->withProtocolVersion($version));
    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name)
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name)
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name)
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): self
    {
        return new self($this->response->withHeader($name, $value));
    }

    public function withAddedHeader(string $name, $value): self
    {
        return new self($this->response->withAddedHeader($name, $value));
    }

    public function withoutHeader(string $name): self
    {
        return new self($this->response->withoutHeader($name));
    }

    public function getBody()
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): self
    {
        return new self($this->response->withBody($body));
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        return new self($this->response->withStatus($code, $reasonPhrase));
    }

    public function getReasonPhrase()
    {
        return $this->response->getReasonPhrase();
    }
}

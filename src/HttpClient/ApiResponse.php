<?php

namespace Asynit\HttpClient;

use Amp\ByteStream\Payload;
use Amp\Http\Client\Response;
use Amp\Http\HttpResponse;

/**
 * @implements \ArrayAccess<string|int, mixed>
 */
class ApiResponse extends HttpResponse implements \ArrayAccess
{
    /**
     * @var array<string|int, mixed>|null
     */
    private mixed $data = null;

    public function __construct(private readonly Response $response)
    {
        parent::__construct($response->getStatus(), $response->getReason());
    }

    private function ensureBodyIsRead(bool $associative = true): void
    {
        if (null === $this->data) {
            $data = json_decode((string) $this->response->getBody(), $associative, flags: JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new \InvalidArgumentException('The response body is not a valid JSON object.');
            }

            $this->data = $data;
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

        if (null === $offset) {
            $this->data[] = $value;

            return;
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->ensureBodyIsRead();

        unset($this->data[$offset]);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): ?string
    {
        return $this->response->getHeader($name);
    }

    public function getBody(): Payload
    {
        return $this->response->getBody();
    }
}

<?php

namespace Asynit\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class HttpClientConfiguration
{
    public function __construct(
        public float $timeout = 10,
        public int $retry = 0,
        public bool $allowSelfSignedCertificate = false,
        public ?string $baseUri = null,
    ) {
    }

    /**
     * Returns a configuration using the given base URI, unless one was already set explicitly. Used to apply the
     * base URI coming from the command line to a class that configures its own client through this attribute.
     */
    public function withBaseUri(?string $baseUri): self
    {
        if (null === $baseUri || null !== $this->baseUri) {
            return $this;
        }

        return new self($this->timeout, $this->retry, $this->allowSelfSignedCertificate, $baseUri);
    }
}

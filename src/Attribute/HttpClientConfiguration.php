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
     * Run wide defaults. Asynit no longer owns the command line - it is PHPUnit's - so the settings that
     * describe the environment under test rather than the test case come from the environment.
     */
    public static function fromEnvironment(): self
    {
        return new self(
            timeout: (float) (self::env('ASYNIT_TIMEOUT') ?? 10),
            retry: (int) (self::env('ASYNIT_RETRY') ?? 0),
            allowSelfSignedCertificate: filter_var(self::env('ASYNIT_ALLOW_SELF_SIGNED_CERTIFICATE') ?? '', FILTER_VALIDATE_BOOL),
            baseUri: self::env('ASYNIT_HOST'),
        );
    }

    private static function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? getenv($name);

        return \is_string($value) && '' !== $value ? $value : null;
    }

    /**
     * Returns a configuration using the given base URI, unless one was already set explicitly. Used to apply the
     * run wide base URI to a class that configures its own client through this attribute.
     */
    public function withBaseUri(?string $baseUri): self
    {
        if (null === $baseUri || null !== $this->baseUri) {
            return $this;
        }

        return new self($this->timeout, $this->retry, $this->allowSelfSignedCertificate, $baseUri);
    }
}

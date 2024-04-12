<?php

namespace Asynit\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class HttpClientConfiguration
{
    public function __construct(
        public float $timeout = 10,
        public int $retry = 0,
        public bool $allowSelfSignedCertificate = false,
    ) {
    }
}

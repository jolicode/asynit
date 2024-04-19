<?php

namespace Asynit;

/**
 * @internal
 *
 * @template T of object
 */
final class TestSuite
{
    /** @var array<string, Test> */
    public array $tests = [];

    /**
     * @param \ReflectionClass<T> $reflectionClass
     */
    public function __construct(
        public readonly \ReflectionClass $reflectionClass
    ) {
    }
}

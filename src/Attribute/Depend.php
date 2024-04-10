<?php

namespace Asynit\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class Depend
{
    public function __construct(public string $dependency, public bool $skipIfFailed = true)
    {
    }
}

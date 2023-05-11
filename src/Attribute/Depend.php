<?php

namespace Asynit\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Depend
{
    public function __construct(public string $dependency)
    {
    }
}

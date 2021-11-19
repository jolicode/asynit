<?php

namespace Asynit\Annotation;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Depend
{
    public string $dependency;

    public function __construct(string $dependency)
    {
        $this->dependency = $dependency;
    }
}

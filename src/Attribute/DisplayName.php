<?php

declare(strict_types=1);

namespace Asynit\Annotation;

#[\Attribute(\Attribute::TARGET_METHOD)]
class DisplayName
{
    public function __construct(public string $name)
    {
    }
}

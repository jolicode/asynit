<?php

declare(strict_types=1);

namespace Asynit\Annotation;

#[\Attribute(\Attribute::TARGET_METHOD)]
class DisplayName
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

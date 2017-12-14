<?php

declare(strict_types=1);

namespace Asynit\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class DisplayName
{
    private $name;

    public function __construct($name)
    {
        if (is_array($name)) {
            $name = current($name);
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

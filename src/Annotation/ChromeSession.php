<?php

declare(strict_types=1);

namespace Asynit\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ChromeSession
{
    private $name;

    public function __construct($name = null)
    {
        if (is_array($name)) {
            $name = current($name);
        }

        if (null === $name || false === $name) {
            $name = uniqid('chrome-session-', true);
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

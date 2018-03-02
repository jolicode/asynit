<?php

namespace Asynit\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Depend
{
    private $dependency;

    public function __construct(array $dependency)
    {
        $this->dependency = $dependency['value'];
    }

    /**
     * @return mixed
     */
    public function getDependency()
    {
        return $this->dependency;
    }
}

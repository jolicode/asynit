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

    public function __construct($dependency)
    {
        if (is_array($dependency)) {
            $dependency = current($dependency);
        }

        $this->dependency = $dependency;
    }

    /**
     * @return mixed
     */
    public function getDependency()
    {
        return $this->dependency;
    }
}

<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return (new \Asynit\Application())->run();

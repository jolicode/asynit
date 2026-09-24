<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        // Test classes now extend PHPUnit's TestCase, which turns on PHPUnit specific fixers. Asynit
        // deliberately supports snake_case test names, and renaming a method silently breaks the #[Depend]
        // attributes pointing at it.
        'php_unit_method_casing' => false,
    ])
    ->setFinder($finder)
;

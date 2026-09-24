<?php

declare(strict_types=1);

/*
 * Migration set for asynit test suites written against the pre-PHPUnit engine.
 *
 * Point your own rector.php at it:
 *
 *     use Rector\Config\RectorConfig;
 *
 *     return RectorConfig::configure()
 *         ->withPaths([__DIR__ . '/tests'])
 *         ->withSets([__DIR__ . '/vendor/jolicode/asynit/config/rector/asynit-phpunit.php']);
 *
 * It covers what can be rewritten safely. UPGRADE.md lists what it deliberately leaves to you.
 */

use Asynit\Rector\AsynitTestCaseToPHPUnitRector;
use Asynit\Rector\OnCreateToBeforeRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return RectorConfig::configure()
    ->withRules([
        AsynitTestCaseToPHPUnitRector::class,
        OnCreateToBeforeRector::class,
    ])
    ->withConfiguredRule(RenameClassRector::class, [
        // The attributes asynit no longer owns, because PHPUnit has its own.
        'Asynit\Attribute\Test' => 'PHPUnit\Framework\Attributes\Test',
        'Asynit\Attribute\DisplayName' => 'PHPUnit\Framework\Attributes\TestDox',
    ])
    ->withConfiguredRule(RenameMethodRector::class, [
        // Asynit's assertion trait kept the PHPUnit 8 spellings, which PHPUnit has since removed. Only the
        // unambiguous ones are renamed here; see UPGRADE.md for the rest.
        new MethodCallRename('PHPUnit\Framework\TestCase', 'assertRegExp', 'assertMatchesRegularExpression'),
        new MethodCallRename('PHPUnit\Framework\TestCase', 'assertNotRegExp', 'assertDoesNotMatchRegularExpression'),
        new MethodCallRename('PHPUnit\Framework\TestCase', 'assertFileNotExists', 'assertFileDoesNotExist'),
        new MethodCallRename('PHPUnit\Framework\TestCase', 'assertNotIsReadable', 'assertIsNotReadable'),
        new MethodCallRename('PHPUnit\Framework\TestCase', 'assertNotIsWritable', 'assertIsNotWritable'),
    ]);

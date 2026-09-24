<?php

namespace Asynit\Runner;

use PHPUnit\Util\ExcludeList;

/**
 * Keeps asynit's scheduler and the event loop it runs on out of the stack traces PHPUnit prints, the same way
 * PHPUnit keeps its own frames out of them. Without this every failure is followed by a dozen frames of
 * fiber plumbing before the line that actually matters.
 *
 * @internal
 */
final class StackTraceFilter
{
    public static function register(): void
    {
        $root = \dirname(__DIR__, 2);

        // src/ is asynit's own code, override/ the classes it substitutes for PHPUnit's; a failure should
        // point at the test, not at either of them.
        foreach (['src', 'override'] as $directory) {
            self::exclude($root.'/'.$directory);
        }

        foreach (['amphp/amp', 'amphp/sync', 'revolt/event-loop'] as $package) {
            self::exclude($root.'/vendor/'.$package);
        }
    }

    private static function exclude(string $directory): void
    {
        if ('' === $directory || !is_dir($directory)) {
            return;
        }

        ExcludeList::addDirectory($directory);
    }
}

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
        /** @var non-empty-string $asynit */
        $asynit = \dirname(__DIR__);
        ExcludeList::addDirectory($asynit);

        foreach (['amphp/amp', 'amphp/sync', 'revolt/event-loop'] as $package) {
            $directory = \dirname(__DIR__, 2).'/vendor/'.$package;

            if (is_dir($directory)) {
                ExcludeList::addDirectory($directory);
            }
        }
    }
}

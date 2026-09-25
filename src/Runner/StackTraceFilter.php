<?php

namespace Asynit\Runner;

use Composer\InstalledVersions;
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

        // src/ is asynit's own code, override/ the classes it substitutes for PHPUnit's and bin/ its entry
        // point; a failure should point at the test, not at any of them.
        foreach (['bin', 'src', 'override'] as $directory) {
            self::exclude($root.'/'.$directory);
        }

        // Resolved through Composer rather than from asynit's root: once asynit is itself a dependency, they
        // sit next to it, not in a vendor/ of its own.
        foreach (InstalledVersions::getInstalledPackages() as $package) {
            if (!str_starts_with($package, 'amphp/') && !str_starts_with($package, 'revolt/')) {
                continue;
            }

            $path = InstalledVersions::getInstallPath($package);

            if (null !== $path) {
                self::exclude(realpath($path) ?: $path);
            }
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

<?php

namespace Asynit\PHPUnit;

use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\TextUI\CliArguments\Builder as CliBuilder;
use PHPUnit\TextUI\Configuration\Registry as ConfigurationRegistry;
use PHPUnit\TextUI\XmlConfiguration\DefaultConfiguration;

/**
 * Brings up the little bit of ambient state PHPUnit's TestCase needs when it is driven by our own runner
 * rather than by PHPUnit's TextUI application.
 *
 * @internal
 */
final class Bootstrap
{
    private static bool $done = false;

    public static function boot(ResultCollector $results): void
    {
        if (self::$done) {
            return;
        }

        self::$done = true;

        // TestCase::runBare() and the per test runner read the configuration registry; the defaults are what
        // we want, we only need the registry to be populated.
        ConfigurationRegistry::init(
            new CliBuilder()->fromParameters(['asynit']),
            DefaultConfiguration::create(),
        );

        // Subscribers have to be in place before the facade is sealed, and the facade has to be sealed before
        // any event is emitted.
        $results->register();

        EventFacade::instance()->seal();
    }
}

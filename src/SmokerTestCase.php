<?php

declare(strict_types=1);

namespace Asynit;

use Symfony\Component\DomCrawler\Crawler;

class SmokerTestCase extends TestCase
{
    const DISCOVERY_DEFAULT_LIMIT = 1000;

    static private $uris = [];

    public function smokeTest($data)
    {
        /** @var SmokeTest $test */
        list($uri, $configuration, $test) = $data;

        $response = yield $this->get($uri);

        if (is_array($configuration['status'])) {
            static::assertContains($response->getStatusCode(), $configuration['status']);
        } else {
            static::assertStatusCode($configuration['status'], $response);
        }

        if (!isset($configuration['discovery'])) {
            return;
        }

        $discovery = $configuration['discovery'];

        self::$uris[$uri] = true;

        if (
            (isset($discovery['enabled']) && !$discovery['enabled']) // Enabled by default
            || (isset($discovery['depth']) && $discovery['depth'] === 0)
            || $this->hasReachedDiscoveryLimit($discovery)
        ) {
            return;
        }

        $recursiveConfiguration = $configuration;
        $recursiveConfiguration['discovery']['limit'] = $discovery['limit'] ?? self::DISCOVERY_DEFAULT_LIMIT;

        if (isset($discovery['depth']) && $discovery['depth'] >= 1) {
            $recursiveConfiguration['discovery']['depth'] = ((int) $discovery['depth']) - 1;
        } else {
            $recursiveConfiguration['discovery']['depth'] = -1;
        }

        $crawler = new Crawler((string) $response->getBody(), $uri);
        $links = $crawler->filterXPath('//a')->links();

        foreach ($links as $link) {
            $uri = $link->getUri();
            $fragment = parse_url($uri, PHP_URL_FRAGMENT);
            $uri = str_replace('#' . $fragment, '', $uri);

            if ($this->hasReachedDiscoveryLimit($discovery)) {
                return;
            }

            if (
                isset(self::$uris[$uri])
                || (isset($discovery['match']) && !preg_match(sprintf('~%s~i', $discovery['match']), $uri))
            ) {
                continue;
            }

            $childTest = new SmokeTest(new \ReflectionMethod(self::class, 'smokeTest'), $uri);
            $argument = [$uri, $recursiveConfiguration, $childTest];
            $childTest->addArgument($argument, $childTest);

            $test->getPool()->addTest($childTest);
            self::$uris[$uri] = true;
        }
    }

    private function hasReachedDiscoveryLimit(array $discovery)
    {
        return count(self::$uris) >= ($discovery['limit'] ?? self::DISCOVERY_DEFAULT_LIMIT);
    }
}

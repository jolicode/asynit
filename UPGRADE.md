# Upgrading to the PHPUnit engine

Asynit no longer has a runner of its own. `bin/asynit` boots PHPUnit, so the CLI, the configuration file, the
output, the loggers and the exit codes are PHPUnit's. What is left of asynit is what PHPUnit cannot do: run
tests concurrently, and order them with a `#[Depend]` graph that can point at any method of any class.

Most of the migration is mechanical and is done by the Rector set shipped with asynit. The rest is listed
under [What Rector will not do](#what-rector-will-not-do).

## Run the Rector set

```console
composer require --dev rector/rector
```

```php
<?php
// rector.php
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/tests'])
    // keeps the diff readable: imports the classes it introduces and drops the ones it orphans
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSets([__DIR__.'/vendor/jolicode/asynit/config/rector/asynit-phpunit.php']);
```

```console
vendor/bin/rector process
vendor/bin/rector process
```

**Run it twice.** The first pass makes your classes extend `PHPUnit\Framework\TestCase`; the assertion renames
only match once that is true, so they land on the second pass.

### What it changes

| Before | After |
| --- | --- |
| `#[Asynit\Attribute\TestCase]` on the class | `extends PHPUnit\Framework\TestCase` |
| `use Asynit\Assert\AssertCaseTrait;` | removed, PHPUnit's `TestCase` carries the assertions |
| `#[Asynit\Attribute\Test]` | `#[PHPUnit\Framework\Attributes\Test]` |
| `#[Asynit\Attribute\DisplayName('...')]` | `#[PHPUnit\Framework\Attributes\TestDox('...')]` |
| `#[OnCreate] public function boot(HttpClientConfiguration $c)` | `#[Before] public function boot()` |
| `assertRegExp` / `assertNotRegExp` | `assertMatchesRegularExpression` / `assertDoesNotMatchRegularExpression` |
| `assertFileNotExists` | `assertFileDoesNotExist` |

`#[Depend]`, `#[HttpClientConfiguration]`, the HTTP client traits (`$this->get()`, `$this->post()`, …) and
`assertStatusCode` / `assertContentType` / `assertHtml` are unchanged.

PHPUnit's own `#[Depends]` and `#[DependsExternal]` work too, and are ordered by the same graph, so you can
mix them or move to them over time. `#[DependsOnClass]` is not supported.

A class that already extends something is left alone rather than having its parent rewritten — make it extend
`PHPUnit\Framework\TestCase` yourself, or have its own base class do so.

## What Rector will not do

### Test discovery: file and class names

Asynit found tests through `#[TestCase]`; PHPUnit finds them by file name. By default it only looks at
`*Test.php`, and the class has to match the file name.

Rather than rename every file, widen the suffix in `phpunit.xml`:

```xml
<testsuites>
    <testsuite name="asynit">
        <directory suffix=".php">tests</directory>
    </testsuite>
</testsuites>
```

A method is a test if it is named `test*` or carries `#[Test]` — the same rule asynit used, so nothing to do
there.

### The command line

Asynit's options are gone. The ones that describe the run rather than a test case are environment variables
now, because PHPUnit's CLI is not asynit's to extend.

| Before | After |
| --- | --- |
| `bin/asynit tests` | `bin/asynit` (paths come from `phpunit.xml`) |
| `--concurrency 5` | `ASYNIT_CONCURRENCY=5` |
| `--host https://api.example.com` | `ASYNIT_HOST=https://api.example.com` |
| `--timeout 30` | `ASYNIT_TIMEOUT=30` |
| `--retry 2` | `ASYNIT_RETRY=2` |
| `--allow-self-signed-certificate` | `ASYNIT_ALLOW_SELF_SIGNED_CERTIFICATE=1` |
| `--report report.xml` | `--log-junit report.xml` |
| `--filter foo` | `--filter foo` (PHPUnit's, regex over `Class::method`) |
| `--bootstrap file.php` | `--bootstrap file.php` (or `bootstrap=` in `phpunit.xml`) |
| `--order` | no equivalent; asynit always orders by the dependency graph |

### Assertions with no direct equivalent

These came from asynit's bovigo-backed trait and have to be rewritten by hand:

| Before | Do this instead |
| --- | --- |
| `assertContains($needle, $haystack)` | `assertStringContainsString` for strings, `assertContains` for arrays — the old one accepted both, so Rector cannot pick for you |
| `assertNotContains($needle, $haystack)` | `assertStringNotContainsString` or `assertNotContains`, same reason |
| `assertInternalType('int', $v)` | `assertIsInt($v)`, and the rest of the `assertIs*` family |
| `assertNotInternalType('int', $v)` | `assertIsNotInt($v)` |
| `assertEquals($e, $a, $message, $delta)` | `assertEqualsWithDelta($e, $a, $delta, $message)` — PHPUnit dropped the fourth argument |
| `assertNotEquals($e, $a, $message, $delta)` | `assertNotEqualsWithDelta($e, $a, $delta, $message)` |
| `assertContainsSubset($other, $subset)` | no PHPUnit equivalent; assert on the parts you care about |
| `$this->assert($value, $predicate, $description)` | the bovigo escape hatch is gone; use the matching PHPUnit assertion |

`assertContains` and `assertInternalType` are the two worth grepping for first — they are the most common and
the least safe to rewrite blindly.

### Tests that assert nothing

PHPUnit reports a test that performs no assertion as risky. Asynit did not. A test that only exists to produce
a value for its dependents is not flagged — asynit knows it is a producer — but a method named `test*` that
asserts nothing is. Either assert something or mark it:

```php
#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
public function testProducesAToken(): string
{
    return 'token';
}
```

### Things that are simply gone

* **Code coverage** is not collected: PHPUnit drives it through a per-test singleton that concurrency breaks.
* **PHPUnit's deprecation and notice reporting** is not produced, for the same reason. PHP errors still fail
  the test, as they did under asynit.
* **Per-test assertion counts** are only accurate with `ASYNIT_CONCURRENCY=1`. The run total is always right.

`src/PHPUnit/README.md` explains why for each of these.

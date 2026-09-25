# asynit

Concurrent test runner for HTTP / API and more, built on top of PHPUnit.

Asynit is PHPUnit: same test classes, same assertions, same CLI, configuration, output, loggers and exit codes.
What it adds is what PHPUnit cannot do:

* tests run **concurrently** on fibers, so tests waiting on I/O (HTTP requests, …) overlap;
* a **`#[Depend]` attribute** that orders tests and passes a test's return value to the tests depending on it,
  pointing at any method of any class, whether it is a test or not.

Coming from asynit 0.17 or earlier? See [UPGRADE.md](UPGRADE.md), a Rector set does most of the migration.
How asynit plugs into PHPUnit is described in [ARCHITECTURE.md](ARCHITECTURE.md).

## Install

```console
composer require --dev jolicode/asynit
```

Asynit requires PHP 8.4 and ships with PHPUnit 13.2.

## Writing a test

A test is a regular PHPUnit test: a class extending `PHPUnit\Framework\TestCase`, whose test methods are
named `test*` or carry `#[PHPUnit\Framework\Attributes\Test]`. Assertions are PHPUnit's, and a test fails
when an exception is thrown.

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertSame('foo', 'foo');
    }
}
```

## Running the tests

```console
vendor/bin/asynit
```

Test discovery, paths, filters and reports are PHPUnit's, so they come from your `phpunit.xml` and from
PHPUnit's options (`--filter`, `--log-junit`, `--bootstrap`, …):

```xml
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="api">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Settings describing the run itself are read from the environment:

| Variable | Default | Meaning |
| --- | --- | --- |
| `ASYNIT_CONCURRENCY` | `10` | how many tests may run at once |
| `ASYNIT_HOST` | none | base URI prepended to requests made with a relative URI |
| `ASYNIT_TIMEOUT` | `10` | default HTTP timeout, in seconds |
| `ASYNIT_RETRY` | `0` | default number of HTTP retries |
| `ASYNIT_ALLOW_SELF_SIGNED_CERTIFICATE` | `false` | disable TLS peer verification |

```console
ASYNIT_CONCURRENCY=20 ASYNIT_HOST=https://api.example.com vendor/bin/asynit --log-junit report.xml
```

## Using the HTTP client

The `Asynit\HttpClient\HttpClientWebCaseTrait` trait gives your test case an HTTP client, built on
[amphp/http-client](https://github.com/amphp/http-client), and a few HTTP assertions: `assertStatusCode()`,
`assertContentType()` and `assertHtml()`.

```php
use Asynit\HttpClient\HttpClientWebCaseTrait;
use PHPUnit\Framework\TestCase;

class HomepageTest extends TestCase
{
    use HttpClientWebCaseTrait;

    public function testGet(): void
    {
        $response = $this->get('https://example.com', headers: ['Accept-Language' => 'fr']);

        $this->assertStatusCode(200, $response);
        $this->assertHtml($response);
    }
}
```

`get()`, `post()`, `put()`, `patch()`, `delete()` and `options()` take the URI, the headers and the body, and
return an `Amp\Http\Client\Response`.

For JSON APIs, use `Asynit\HttpClient\HttpClientApiCaseTrait` instead. Its methods take the URI, an array
encoded as the JSON body, and the headers. The response can be read as an array:

```php
use Asynit\HttpClient\HttpClientApiCaseTrait;
use PHPUnit\Framework\TestCase;

class PostApiTest extends TestCase
{
    use HttpClientApiCaseTrait;

    public function testCreate(): void
    {
        $response = $this->post('/posts', ['title' => 'Hello']);

        $this->assertStatusCode(201, $response);
        $this->assertSame('Hello', $response['title']);
    }
}
```

Relative URIs such as `/posts` are resolved against `ASYNIT_HOST`.

### Configuring the client of a test case

The environment variables set the defaults for the whole run. A test case can use its own settings with the
`Asynit\Attribute\HttpClientConfiguration` attribute:

```php
use Asynit\Attribute\HttpClientConfiguration;
use Asynit\HttpClient\HttpClientApiCaseTrait;
use PHPUnit\Framework\TestCase;

#[HttpClientConfiguration(timeout: 30, retry: 2, allowSelfSignedCertificate: true)]
class SlowEndpointTest extends TestCase
{
    use HttpClientApiCaseTrait;
}
```

It still inherits `ASYNIT_HOST`, unless it sets `baseUri` itself.

## Dependency between tests

Sometimes a test needs a value produced by another one, like an authentication token. The
`Asynit\Attribute\Depend` attribute declares that a test depends on another method: it runs once that method
has succeeded, and receives its return value as argument.

Given three tests `A`, `B` and `C`, where `C` depends on `A`: `A` and `B` run concurrently, and `C` starts as
soon as `A` has passed, with the value `A` returned.

```php
namespace App\Tests;

use Asynit\Attribute\Depend;
use Asynit\HttpClient\HttpClientApiCaseTrait;
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    use HttpClientApiCaseTrait;

    public function testLogin(): string
    {
        $response = $this->post('/login', ['username' => 'user', 'password' => 'test']);

        $this->assertStatusCode(200, $response);

        return $response['token'];
    }

    #[Depend('testLogin')]
    public function testAuthenticatedRequest(string $token): void
    {
        $response = $this->get('/api', headers: ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

A dependency can live in another class, by using its fully qualified name:

```php
namespace App\Tests;

use Asynit\Attribute\Depend;
use Asynit\HttpClient\HttpClientApiCaseTrait;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    use HttpClientApiCaseTrait;

    #[Depend('App\Tests\SecurityTest::testLogin')]
    public function testGet(string $token): void
    {
        $response = $this->get('/posts', headers: ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

`#[Depend]` is repeatable: the values are passed in the order the attributes are declared. When a dependency
fails, the tests depending on it are skipped; pass `skipIfFailed: false` to run them anyway, without the value
of the failed dependency.

PHPUnit's own `#[Depends]` and `#[DependsExternal]` (and their `UsingDeepClone` / `UsingShallowClone`
variants) work too and are ordered by the same graph. `#[DependsOnClass]` is not supported.

A circular dependency is reported as an error before any test runs.

## Test organization

A token is often needed by many tests, and fetching it is not a test in itself. `#[Depend]` can point at any
public method, not only tests: a method that is not a test is run once, before the tests depending on it,
is not reported, and is not required to perform an assertion.

The class declaring it must extend `PHPUnit\Framework\TestCase` and must not be abstract, since asynit
instantiates it to run the method. Name its file so PHPUnit does not collect it as a test suite (with the
default `*Test.php` suffix, `TokenFetcher.php` is left alone).

```php
namespace App\Tests;

use Asynit\HttpClient\HttpClientApiCaseTrait;
use PHPUnit\Framework\TestCase;

class TokenFetcher extends TestCase
{
    use HttpClientApiCaseTrait;

    public function fetchUserToken(): string
    {
        return $this->fetchToken('user@example.com', 'password');
    }

    private function fetchToken(string $email, string $password): string
    {
        $response = $this->post('/users/token', ['email' => $email, 'password' => $password]);

        return $response['token'];
    }
}
```

```php
namespace App\Tests;

use Asynit\Attribute\Depend;
use Asynit\HttpClient\HttpClientApiCaseTrait;
use PHPUnit\Framework\TestCase;

class OrganizationTest extends TestCase
{
    use HttpClientApiCaseTrait;

    #[Depend('App\Tests\TokenFetcher::fetchUserToken')]
    public function testListOrganizations(string $token): void
    {
        $response = $this->get('/organizations', headers: ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

Whatever the number of tests depending on it, `fetchUserToken()` runs only once.

## Limitations

Running tests concurrently has a few consequences on PHPUnit features: code coverage and deprecation / notice
reporting are not available, process isolation and `--order-by` are ignored. See
[ARCHITECTURE.md](ARCHITECTURE.md#known-limitations) for the full list.

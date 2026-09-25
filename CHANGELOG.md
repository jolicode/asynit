## 0.18.0 - 25/09/2026

* [BC BREAK] Asynit now runs on PHPUnit: `bin/asynit` boots PHPUnit, whose CLI, configuration, output, loggers
  and exit codes replace asynit's own. Test classes extend `PHPUnit\Framework\TestCase` and use its assertions.
  Asynit keeps concurrent execution and `#[Depend]`, which now also orders PHPUnit's `#[Depends]`. See
  [UPGRADE.md](UPGRADE.md), and the Rector set in `config/rector/asynit-phpunit.php` for the mechanical part.
* [BC BREAK] Run settings moved to environment variables: `ASYNIT_CONCURRENCY`, `ASYNIT_HOST`, `ASYNIT_TIMEOUT`,
  `ASYNIT_RETRY` and `ASYNIT_ALLOW_SELF_SIGNED_CERTIFICATE`
* [BC BREAK] Drop support for PHP 8.2 and 8.3
* Fix self signed certificates being always allowed. TLS peer verification is now enabled unless
  `ASYNIT_ALLOW_SELF_SIGNED_CERTIFICATE` is set, so runs against a server with an untrusted certificate that used
  to pass will now fail.
* Implement the base URI (`--host`, now `ASYNIT_HOST`), which was accepted but ignored. A class configuring its
  own client with `#[HttpClientConfiguration]` still inherits it.
* Detect circular dependencies between tests and report them instead of failing with an unrelated error
* Fix output of concurrent tests being attributed to whichever test finished first
* Fix per test assertion counts under concurrency, which also made the detection of tests performing no
  assertion unreliable
* Fix errored and skipped tests being counted twice under concurrency
* Keep amphp and revolt frames out of stack traces when asynit is installed as a dependency

## 0.17.0 - 15/01/2026

* Add Symfony 8 support

## 0.16.0 - 16/03/2025

* Add PHP 8.4 support
* Allow bovigo/assert ^8.0
* Drop support for Symfony < 5.4, 6.0, 6.1, 6.2 and 6.3

## 0.15.0 - 03/06/2024

* Add Symfony 7 support

## 0.14.0 - 19/04/2024

* Add JUnit report

## 0.13.0 - 12/04/2024

* Add command line argument to configure default http client configuration
* Fix allow self signed certificate not used
* [BC BREAK] HTTP test case now rely exclusively on amp http client (no more psr7 or psr18)
* Fix assertions count
* Add a new attribute to configure HttpClient (allow to set timeout)

## 0.12.0 - 11/05/2023

 * [BC BREAK] No more yield, use php fiber instead
 * [BC BREAK] Make http test case as an option
 * [BC BREAK] No more global test case case
 * [BC BREAK] Use PHP attribute instead of annotation
 * Add a new test case trait for API

### Migrating from 0.11

The API for asynit has changed, you need to update your test cases.

#### Before

```php
<?php

class HttpbinTest extends \Asynit\TestCase
{
    /** @\Asynit\Annotation\Depend('getToken') */
    public function testGet($token)
    {
        $response = yield $this->get('https://httpbin.org');
        $response = yield $this->get('http://httpbin.org', ['Authorization' => 'Bearer {token}']);
        $this->assertStatusCode(200, $response);
    }

    public function getToken()
    {
        return 'my_token';
    }
}
```

#### After

```php

<?php

#[\Asynit\Attribute\TestCase]
class HttpbinTest
{
    use \Asynit\HttpClient\HttpClientWebCaseTrait;

    #[\Asynit\Attribute\Depend('getToken')]
    public function testGet($token)
    {
        $response = $this->get('http://httpbin.org', ['Authorization' => 'Bearer {token}']);
        $this->assertStatusCode(200, $response);
    }

    public function getToken()
    {
        return 'my_token';
    }
}
```

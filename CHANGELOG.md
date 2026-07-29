## Changes

* Fix self signed certificates being always allowed, whether `--allow-self-signed-certificate` was passed or not.
  TLS peer verification is now enabled unless the flag is given, so runs against a server with an untrusted
  certificate that used to pass will now fail without the flag.
* Fix tests inherited from a parent class: they were all collapsed into a single test and ran against the parent
  class instead of the test case class. A `#[TestCase]` class that is abstract is now skipped.
* Fix a fatal error when generating a report for a suite whose tests were all skipped
* Implement `--host`, which was accepted but ignored: it is now used as the base URI for requests made with a
  relative URI. A class configuring its own client with `#[HttpClientConfiguration]` still inherits it.
* Detect circular dependencies between tests and report them instead of failing with an unrelated error
* Fix output of concurrent tests being attributed to whichever test finished first
* Fix test durations including the time spent waiting for a free concurrency slot

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

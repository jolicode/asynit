## Changes

## 0.14.0 - 19/04/2024

* Add JUnit report

## 0.13.0 - 12/04/2024

* Add command line argument to configure default http client configuration
* Fixed allow self signed certificate not used
* **[BC BREAK]** HTTP test case now rely exclusively on amp http client (no more psr7 or psr18)
* Fix assertions count
* Add a new attribute to configure HttpClient (allow to set timeout)

## 0.12.0 - 11/05/2023

 * **[BC BREAK]** No more yield, use php fiber instead
 * **[BC BREAK]** Make http test case as an option
 * **[BC BREAK]** No more global test case case
 * **[BC BREAK]** Use PHP attribute instead of annotation
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
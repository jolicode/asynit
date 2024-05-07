# asynit

Asynchronous (if library use fiber) testing library runner for HTTP / API and more...

## Install

```
composer require --dev jolicode/asynit
```

## Usage

### Writing a test

Asynit will read PHP's classes to find available Test using the `Asynit\Attribute\TestCase` attribute. You need to
create a test class in some directory, which will have the `TestCase` attribute of
Asynit:

```php
use Asynit\Attribute\TestCase;

#[TestCase]
class ApiTest
{
}
```

Then you can add some tests that will use the API of the TestCase class:

```php
use Asynit\Attribute\Test;
use Asynit\Attribute\TestCase;

#[TestCase]
class ApiTest
{
    #[Test]
    public function my_test()
    {
        // do some test
    }
}
```

Note: All test methods should be prefixed by the `test` keyword or use the `Asynit\Attribute\Test` anotation. All others
methods will not be executed automatically.

A test fail when an exception occurs during the test

### Using assertion

Asynit provide trait to ease the writing of test. You can use the `Asynit\AssertCaseTrait` trait to use the assertion.

```php
use Asynit\Attribute\Test;
use Asynit\Attribute\TestCase;

#[TestCase]
class ApiTest
{
    use Asynit\AssertCaseTrait;

    #[Test]
    public function my_test()
    {
        $this->assertSame('foo', 'foo');
    }
}
```

All assertions supported by PHPUnit are also supported by Asynit thanks to the
[bovigo-assert](https://github.com/mikey179/bovigo-assert) library.
But you can use your own as long as it's throw an exception on failure.

### Running the test

For running this test you will only need to use the PHP file provided by this
project:

```bash
$ php vendor/bin/asynit path/to/the/file.php
```

If you have many test files, you can run Asynit with a directory

```bash
$ php vendor/bin/asynit path/to/the/directory
```

### Using HTTP Client

Asynit provide an optional `Asynit\HttpClient\HttpClientWebCaseTrait` trait that you can use to make HTTP request. You will need to install `amphp/http-client` and
`nyholm/psr7` to use it.

```php
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\HttpClientWebCaseTrait;

#[TestCase]
class FunctionalHttpTests
{
    use HttpClientWebCaseTrait;

    public function testGet()
    {
        $response = $this->get('https//example.com');

        $this->assertStatusCode(200, $response);
    }
}
```

You can also use a more oriented API trait `Asynit\HttpClient\HttpClientApiCaseTrait` that will allow you to write test like this:



```php
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\HttpClientApiCaseTrait;

#[TestCase]
class FunctionalHttpTests
{
    use HttpClientApiCaseTrait;

    public function testGet()
    {
        $response = $this->get('https//example.com');

        $this->assertStatusCode(200, $response);
        $this->assertSame('bar', $response['foo']);
    }
}
```

### Dependency between tests

Sometime a test may need a value from the result of another test, like an
authentication token that need to be available for some requests (or a cookie
defining the session).

Asynit provides a `Depend` attribute which allows you to specify that a test is
dependent from another one.

So if you have 3 tests, `A`, `B` and `C` and you say that `C` depend on `A`;
Test `A` and `B` will be run in parallel and once `A` is completed and
successful, `C` will be run with the result from `A`.

Let's see an example:

```php
use Asynit\Attribute\Depend;
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\HttpClientApiCaseTrait;

#[TestCase]
class SecurityTest extends TestCase
{
    use HttpClientApiCaseTrait;

    public function testLogin()
    {
        $response = $this->post('/', ['username' => user, 'password' => 'test']);

        $this->assertStatusCode(200, $response);

        return $response->getBody()->getContents();
    }

    #[Depend("testLogin")]
    public function testAuthenticatedRequest(string $token)
    {
        $response = $this->get('/api', headers: ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

Here `testAuthenticatedRequest` will only be run after `testLogin` has been
completed. You can also use dependency between different test case. The previous
test case is under the `Application\ApiTest` namespace and thus we can write
another test case like this:

```php
use Asynit\Attribute\Depend;
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\HttpClientApiCaseTrait;

#[TestCase]
class PostTest
{
    #[Depend("Application\ApiTest\SecurityTest::testLogin")]
    public function testGet($token)
    {
        $response = $this->get('/posts', headers: ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

### Test Organization

It's really common to reuse this token in a lot of test, and maybe you don't need test when fetching the token.
Asynit allow you to depend on any method of any class.

So you could write a `TokenFetcherClass` that will fetch the token and then use it in your test.

```php
namespace App\Tests;

use Asynit\HttpClient\HttpClientApiCaseTrait;

class TokenFetcher
{
    use HttpClientApiCaseTrait;

    protected function fetchToken(string $email, string $password = 'password')
    {
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        $response = $this->post('/users/token', ['username' => 'user', 'password' => 'test']);

        return $response['token'];
    }
    
    protected function fetchUserToken()
    {
        return $this->fetchToken('email@example.com', 'password');
    }
}
```

Then in your test class you will be able to call this method:

```php
namespace App\Tests;

use Asynit\Attribute\Depend;
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\HttpClientApiCaseTrait;

#[TestCase]
class OrganizationTest
{
    use HttpClientApiCaseTrait;

    #[Depend("App\Tests\TokenFetcher::fetchUserToken")]
    public function test_api_method_with_token(string $token)
    {
        $response = $this->get('/api', headers: ['X-Auth-Token' => $token]);

        // ...
    }
}
```

As you may notice, the `fetchUserToken` method does not start with `test`. Thus
by default this method will not be included in the test suite. But as it is a
dependency of a test, it will be included as a regular test in the global test
suite and will leverage the cache system.


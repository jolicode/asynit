# asynit

Asynchronous (using coroutine) HTTP Request Testing Library for API and more...

## Install

```
composer require --dev jolicode/asynit
```

## Usage

### Asynit

#### Basic usage

Asynit will read PHP's classes and try to mimic the PHPUnit API. You need to
create a test class in some directory, which will extend the `TestCase` class of
Asynit:

```php
use Asynit\TestCase;

class ApiTest extends TestCase
{
}
```

Then you can add some tests that will use the API of the TestCase class:

```php
use Asynit\TestCase;

class ApiTest extends TestCase
{
    public function testGet()
    {
        $response = yield $this->get('http://my-site-web');

        $this->assertSame(200, $response->getStatusCode());
        // or
        $this->assertStatusCode(200, $response);
    }
}
```

Note: All test methods should be prefixed by the `test` keyword. All others
methods will not be executed automatically.

Here we perform a `GET` request on `http://my-site-web` then we get the
`$response` by using the `yield` operator. This operator must be understood
like an `await` in other language (C# / JavaScript) which is feasible by using
the [amp](https://github.com/amphp/amp) framework.

All assertions supported by PHPUnit are also supported by Asynit thanks to the
[bovigo-assert](https://github.com/mikey179/bovigo-assert) library.
But you can use your own as long as it's throw an exception on failure.

For running this test you will only need to use the PHP file provided by this
project:

```bash
$ php vendor/bin/asynit path/to/the/file.php
```

If you have many test files, you can run Asynit with a directory

```bash
$ php vendor/bin/asynit path/to/the/directory
```

#### Overriding HTTP Client

Like PHPUnit you can add a special method named `setUp` to your test case. This
special method will be run before each test and can also be used to override the
HTTP client.

```php

use Asynit\TestCase;
use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\HttpAsyncClient;
use Http\Message\UriFactory\GuzzleUriFactory;

class ApiTest extends TestCase
{
    public function setUp(HttpAsyncClient $asyncClient): HttpAsyncClient
    {
        $uri = (new GuzzleUriFactory())->createUri('http://httpbin.org');

        return new PluginClient($asyncClient, [
            new BaseUriPlugin($uri)
        ]);
    }
}
```

You should always decorate the client and not trying to return a new one (unless
you know what you are doing).

#### Dependency between tests

Sometime a test may need a value from the result of another test, like an
authentication token that need to be available for some requests (or a cookie
defining the session).

Asynit provides a `Depend` annotation which allows you to specify that a test is
dependent from another one.

So if you have 3 tests, `A`, `B` and `C` and you say that `C` depend on `A`;
Test `A` and `B` will be run in parallel and once `A` is completed and
successful, `C` will be run with the result from `A`.

Let's see an example:

```php

namespace Application\ApiTest;

use Asynit\Annotation\Depend;
use Asynit\TestCase;

class SecurityTest extends TestCase
{
    public function testLogin()
    {
        $response = yield $this->post('/', [], '{ "username": "user", "password": "pass" }');

        $this->assertStatusCode(200, $response);

        return $response->getBody()->getContents();
    }

    /**
     * @Depend("testLogin")
     */
    public function testAuthenticatedRequest(string $token)
    {
        $response = yield $this->get('/api', ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

Here `testAuthenticatedRequest` will only be run after `testLogin` has been
completed. You can also use dependency between different test case. The previous
test case is under the `Application\ApiTest` namespace and thus we can write
another test case like this:

```php
class PostTest
{
    /**
     * @Depend("Application\ApiTest\SecurityTest::testLogin")
     */
    public function testGet($token)
    {
        $response = yield $this->get('/posts', ['X-Auth-Token' => $token]);

        $this->assertStatusCode(200, $response);
    }
}
```

#### Test Organization

It's really common to have an `abstract WebTestCase` in your project where you can
define many helpers to ease the writing of tests.

Here is an example:

```php
namespace App\Tests;

use Asynit\TestCase;

abstract class WebTestCase extends TestCase
{
    protected function fetchToken(string $email, string $password = 'password')
    {
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        $response = yield $this->post('/users/token', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], http_build_query($payload));

        $this->assertSame(200, $response->getStatusCode());

        $content = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('token', $content);

        return $content['token'];
    }
}
```

Then in your test class you will be able to call this method:

```php
namespace App\Tests;

class OrganizationTest extends WebTestCase
{
    public function test_user_can_get_its_information()
    {
        $token = yield from $this->fetchToken('email@example.com');

        // ...
    }
}
```

If many tests are using the method `WebTestCase::fetchToken` with the same
argument, it could be useful to cache this method. As it is explain in the
[Dependency between tests](#dependency-between-tests) chapter, it's possible to
use the `Depend` annotation:


```php
namespace App\Tests;

use Asynit\TestCase;

abstract class WebTestCase extends TestCase
{
    protected function fetchUserToken()
    {
        return yield from $this->fetchToken('email@example.com', 'password');
    }
}
```

Then in your test you will be able to depend on this method:

```php
namespace App\Tests;

class OrganizationTest extends WebTestCase
{
    /** @Depend("fetchUserToken") */
    public function test_greg_fetch_token(string $userToken)
    {
        // ...
    }
}
```

As you may notice, the `fetchUserToken` method does not start with `test`. Thus
by default this method will not be included in the test suite. But as it is a
dependency of a test, it will be included as a regular test in the global test
suite and will leverage the cache system.

### Smoker

Smoker use the Asynit API to provide a simple way to test many URLs when there
is no need to have a complex logic of testing.

You just have to define a YAML file like the following:

```yaml
"https://jolicode.com/":
    status: 200

"https://jolicode.com/equipe":
    status: 200

"https://jolicode.com/nos-valeurs":
    status: 200
```

And then run the PHP smoker CLI on it:

```yaml
php bin/smoker test.yml
```

In case you want to check all your site without having to maintain a list of
URLs in the YAML file, you can use the discovery feature. This will make the
smoker crawl your website and run asserts on all the matching URLs it find:

```yaml
"https://jolicode.com/":
    status: 200
    discovery:
        enabled: true
        match: 'https://jolicode.com/(.*)'
        # depth: 3
        limit: 1000
```

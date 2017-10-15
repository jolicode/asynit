# asynit

Asynchronous (using coroutine) HTTP Request Testing Library for API or more...

## Install

```
composer require --dev jolicode/asynit
```

## Usage

### Asynit

#### Basic usage

Asynit will read PHP's classes and try to mimic the API of PHPUnit, so you need to a create a test class in some directory,
which will extends the `TestCase` class of Asynit:

```php
use Asynit\TestCase;

class ApiTest extends TestCase
{
}
```

Then you can add some tests that will use the API of the TestCase class:

```php
use Asynit\TestCase;
use Psr\Http\Message\ResponseInterface;

class ApiTest extends TestCase
{
    public function testGet()
    {
        $response = yield $this->get('http://my-site-web');

        self::assertStatusCode(200, $response);
    }
}
```

Here we tell the test to do a GET request on `http://my-site-web` then we get the response by using the `yield` operator.
This operator must but understand like an `await` in other language (C# / Javascript) which is feasible by using the amp framework.

Some assertions are given by this lib, but you can use your own as long as it's throw an exception on failure.

For running this test you will only need to use the PHP file provided by this project:

```bash
$ php vendor/bin/asynit tests-directory/
```

#### Overriding HTTP Client

Like PHPUnit you can add a special method named `setUp` to your test case. This special method will be run before each test
and can also be used to override the http client.

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

You should always decorate the client and not trying to return a new one (unless you know what you are doing).

#### Dependency between tests

Sometime a test may need a value from the result of another test, like an authentication token that need to be available for
some requests (or a cookie defining the session).

Asynit provides a `Depend` annotation which allows you to specify that a test is dependent from another one.

So if you have 3 tests, A, B and C and you say that C depend on A; A and B will be run in parallel and once A is completed
and successful, C will be run with the result from A, let's see an example:

```php

namespace Application\ApiTest;

use Asynit\Annotation\Depend;
use Asynit\TestCase;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Message\UriFactory\GuzzleUriFactory;

class SecurityTest extends TestCase
{
    public function testLogin()
    {
        $response = yield $this->post('/', [], '{ "username": "user", "password": "pass" }');

        self::assertStatusCode(200, $response);

        return $response->getBody()->getContents();
    }
    
    /**
     * @Depend("testLogin")
     */
    public function testAuthenticatedRequest($token)
    {
        $response = yield $this->get('/api', ['X-Auth-Token' => $token]);

        self::assertStatusCode(200, $response);
    }
}
```

Here `testAuthenticatedRequest` will only be run after `testLogin` has been completed. You can also use dependency between different test case.
The previous test case is under the `Application\ApiTest` namespace and thus we can write another test case like this:

```php
class PostTest
{
    /**
     * @Depend("Application\ApiTest\SecurityTest::testLogin")
     */
    public function testGet($token)
    {
        $response = yield $this->get('/posts', ['X-Auth-Token' => $token]);

        self::assertStatusCode(200, $response);
    }
}
```

### Smoker

Smoker use the Asynit API to provide a simple way to test many urls when there is no need to have a complex logic of testing.

You just have to defined a yaml file like the following:

```yaml
"https://jolicode.com/":
    status: 200

"https://jolicode.com/equipe":
    status: 200

"https://jolicode.com/nos-valeurs":
    status: 200
```

And then run the php smoker cli on it:

```yaml
php bin/smoker test.yml
```

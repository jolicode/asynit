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

Here we tell the test to do a GET request on `http://my-site-web` then we pass it a callback that will be called when 
the response will be available. Having this API allows to launch multiple requests in parallels without blocking.

You can also note that there is no assert, this library doesn't provide that, instead it uses exception to detect failure.
A failed test is a test that throws an exception. If you want to use assertion there is numerous library that
can handle this use case.

For running this test you will only need to use the PHP file provided by this project:

```bash
$ php vendor/bin/asynit tests-directory/
```

#### Overriding HTTP Client

Like PHPUnit you can add a special method named `setUp` to your test case. This special method will be run before each test
and can also be used to override the http client.

```php

use Asynit\TestCase;
use Amp\Artax\Client;

class ApiTest extends TestCase
{
    public function setUp(Client $asyncClient): Client
    {
        return $asyncClient;
    }
}
```

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

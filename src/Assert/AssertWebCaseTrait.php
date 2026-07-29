<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Amp\Http\HttpResponse;
use PHPUnit\Framework\Assert;

/**
 * HTTP specific assertions. Everything else comes from PHPUnit's TestCase, which the test class extends.
 */
trait AssertWebCaseTrait
{
    public function assertStatusCode(int $expectedStatus, HttpResponse $response, string $message = ''): void
    {
        Assert::assertSame($expectedStatus, $response->getStatus(), $message ?: 'Assert status code is equals to '.$expectedStatus);
    }

    public function assertContentType(string $expected, HttpResponse $response, string $message = ''): void
    {
        Assert::assertStringContainsString($expected, (string) $response->getHeader('Content-Type'), $message ?: 'Assert content type is "'.$expected.'"');
    }

    public function assertHtml(HttpResponse $response, string $message = ''): void
    {
        $this->assertContentType('text/html', $response, $message);
    }
}

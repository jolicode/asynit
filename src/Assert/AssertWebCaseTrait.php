<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Psr\Http\Message\ResponseInterface;

trait AssertWebCaseTrait
{
    use AssertCaseTrait;

    public static function assertStatusCode($expectedStatus, ResponseInterface $response, $message = null)
    {
        self::assertEquals($expectedStatus, $response->getStatusCode(), $message ?? 'Assert status code is equals to ' . $expectedStatus);
    }

    public static function assertContentType($expected, ResponseInterface $response, $message = null)
    {
        $contentType = $response->getHeaderLine('Content-Type');

        self::assertContains($expected, $contentType, $message ?? 'Assert content type is "' . $expected.'"');
    }

    public static function assertHtml(ResponseInterface $response, $message = null)
    {
        self::assertContentType('text/html', $response, $message);
    }
}

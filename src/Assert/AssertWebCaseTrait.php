<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Psr\Http\Message\ResponseInterface;

trait AssertWebCaseTrait
{
    use AssertCaseTrait;

    public function assertStatusCode($expectedStatus, ResponseInterface $response, $message = null)
    {
        $this->assertEquals($expectedStatus, $response->getStatusCode(), $message ?? 'Assert status code is equals to ' . $expectedStatus);
    }

    public function assertContentType($expected, ResponseInterface $response, $message = null)
    {
        $contentType = $response->getHeaderLine('Content-Type');

        $this->assertContains($expected, $contentType, $message ?? 'Assert content type is "' . $expected.'"');
    }

    public function assertHtml(ResponseInterface $response, $message = null)
    {
        $this->assertContentType('text/html', $response, $message);
    }
}

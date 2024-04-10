<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Amp\Http\HttpResponse;

trait AssertWebCaseTrait
{
    use AssertCaseTrait;

    public function assertStatusCode($expectedStatus, HttpResponse $response, $message = null)
    {
        $this->assertEquals($expectedStatus, $response->getStatus(), $message ?? 'Assert status code is equals to '.$expectedStatus);
    }

    public function assertContentType($expected, HttpResponse $response, $message = null)
    {
        $contentType = $response->getHeader('Content-Type');

        $this->assertContains($expected, $contentType, $message ?? 'Assert content type is "'.$expected.'"');
    }

    public function assertHtml(HttpResponse $response, $message = null)
    {
        $this->assertContentType('text/html', $response, $message);
    }
}

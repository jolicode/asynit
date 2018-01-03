<?php

declare(strict_types=1);

namespace Asynit;

use Asynit\Annotation\ChromeTab;
use Asynit\Extension\Chrome\Client;

class SmokerTestCase extends TestCase
{
    /**
     * @ChromeTab()
     */
    public function smokeTest($data)
    {
        list($url, $expected, $baseDir, $testName) = $data;

        /** @var Client $client */
        $client = yield $this->createChromeClient();
        $response = yield $client->request($url);

        $this->assertStatusCode($expected['status'], $response);

        if (array_key_exists('screenshot', $expected)) {
            $name = $expected['screenshot']['name'] ?? trim($testName, '/');

            yield from $this->doScreenshotAssert($client, $baseDir, $name, $expected['screenshot']['changes'] ?? 5);
        }
    }

    protected function doScreenshotAssert(Client $client, string $baseDir, string $screenshotName, float $changesAllowed)
    {
        // Allow tiny diff - float comparison can be a pain in the ass
        $changesAllowed += 0.01;
        $data = yield $client->screenshot();

        $imageObj = new \imagick();
        $imageObj->readImageBlob($data);

        $resultPath = $baseDir . '/screenshots/result/' . $screenshotName . '.png';
        $expectedPath = $baseDir . '/screenshots/expected/' . $screenshotName . '.png';
        $diffPath = $baseDir . '/screenshots/diff/' . $screenshotName . '.png';

        if (!file_exists($expectedPath)) {
            @mkdir(\dirname($expectedPath), 0755, true);
            $imageObj->writeImage($expectedPath);

            return;
        }

        @mkdir(\dirname($resultPath), 0755, true);
        @mkdir(\dirname($diffPath), 0755, true);

        $expected = new \imagick($expectedPath);
        list($comparedImage, $comparedResult)  = $expected->compareImages($imageObj, \Imagick::METRIC_FUZZERROR);

        $changesPercent = $comparedResult * 100;

        try {
            $this->assertLessThanOrEqual($changesAllowed, $changesPercent, 'Test render changes, if failure please move the result image to the expected directory');
        } catch (\Throwable $exception) {
            $comparedImage->setImageFormat('png');
            $comparedImage->writeImage($diffPath);

            $imageObj->writeImage($resultPath);

            throw $exception;
        }
    }
}

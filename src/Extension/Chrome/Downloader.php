<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use function Amp\ByteStream\pipe;
use function Amp\call;
use function Amp\File\exists;
use Amp\File\Handle;
use function Amp\File\open;
use Amp\Promise;

class Downloader
{
    const CHROME_REVISION = '524617';
    const CHROME_MIRROR = 'https://storage.googleapis.com';

    private $extractDirectory;
    private $client;

    private $downloadUrls = [
        'linux' => '%s/chromium-browser-snapshots/Linux_x64/%d/chrome-linux.zip',
        'mac' => '%s/chromium-browser-snapshots/Mac/%d/chrome-mac.zip',
        'win' => [
            '32' => '%s/chromium-browser-snapshots/Win/%d/chrome-win32.zip',
            '64' => '%s/chromium-browser-snapshots/Win_x64/%d/chrome-win32.zip',
        ]
    ];

    public function __construct(string $extractDirectory = null, Client $client = null)
    {
        if (null === $client) {
            $client = new DefaultClient();
        }

        if (null === $extractDirectory) {
            $extractDirectory = sys_get_temp_dir();
        }

        $this->extractDirectory = $extractDirectory;
        $this->client = $client;
    }

    public function getBinaryPath(): Promise
    {
        return call(function () {
            $executable = 'chrome';

            if ($this->getPlatform() === 'win') {
                $executable = 'chrome.exe';
            }

            $chromeDirectory = $this->extractDirectory . DIRECTORY_SEPARATOR . 'chrome-' . $this->getPlatform();
            $chromeExecutablePath = $chromeDirectory . DIRECTORY_SEPARATOR . $executable;

            if (!yield exists($chromeExecutablePath)) {
                $tmpZipUrl = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chrome.zip';
                $downloadUrl = sprintf($this->getDownloadUrl(), self::CHROME_MIRROR, self::CHROME_REVISION);
                /** @var Response $response */
                /** @var Handle $zipFile */
                list ($response, $zipFile) = yield [
                    $this->client->request($downloadUrl, [
                        Client::OP_MAX_BODY_BYTES => 1024 * 1024 * 200, // 200Mb
                    ]),
                    open($tmpZipUrl, 'w+'),
                ];

                // Writing file
                yield pipe($response->getBody()->getInputStream(), $zipFile);

                $zip = new \ZipArchive();
                $zip->open($tmpZipUrl);
                $zip->extractTo($this->extractDirectory);

                yield \Amp\File\unlink($tmpZipUrl);

                yield \Amp\File\chmod($chromeExecutablePath, 0755);
            }

            return $chromeDirectory . DIRECTORY_SEPARATOR . $executable;
        });
    }

    private function getDownloadUrl(): string
    {
        $arch = 8 * PHP_INT_SIZE; // 32 or 64
        $downloadUrl = $this->downloadUrls[$this->getPlatform()];

        if (\is_array($downloadUrl)) {
            return $downloadUrl[$arch];
        }

        return $downloadUrl;
    }

    private function getPlatform(): string
    {
        $platform = null;

        if (\stripos(PHP_OS, 'darwin') === 0) {
            $platform = 'mac';
        } elseif (\stripos(PHP_OS, 'win') === 0) {
            $platform = 'win';
        } elseif (\stripos(PHP_OS, 'linux') === 0) {
            $platform = 'linux';
        }

        if (null === $platform) {
            throw new \RuntimeException('Unsupported platform : ' . PHP_OS);
        }

        return $platform;
    }
}

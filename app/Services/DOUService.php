<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class DOUService
{
    const URL_LOGIN = "logar.php";
    const URL_DOWNLOAD = "index.php";

    const DOU_SECTIONS = ['DO1', 'DO2', 'DO3', 'DO1E', 'DO2E', 'DO3E'];

    protected Client $client;
    protected CookieJar $cookieJar;
    protected Filesystem $storage;

    public function __construct()
    {
        $this->client = $this->createClient();
        $this->storage = Storage::disk('local');
    }

    private function createClient(): Client
    {
        return new Client([
            'base_uri' => Config::get('inlabs.base_url'),
            'cookies' => true,
            'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT],
            'defaults' => [
                'headers' => [
                    "Content-Type" => "application/x-www-form-urlencoded",
                    "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
                ]
            ]
        ]);
    }

    public function login()
    {
        $this->cookieJar = Cache::remember(
            'inlabs_session_cookie',
            now()->addMinutes(Config::get('inlabs.cookie-cache-time')),
            function () {
                $jar = new CookieJar();

                $this->client->post(self::URL_LOGIN, [
                    'form_params' => Config::get('inlabs.credentials'),
                    'cookies' => $jar
                ]);

                return $jar;
            }
        );
    }

    /**
     * @throws GuzzleException
     */
    public function download($dates = [])
    {
        if (!count($dates)) {
            $dates[] = Carbon::now()->format('Y-m-d');
        }

        foreach ($dates as $date) {
            foreach (self::DOU_SECTIONS as $section) {
                $this->downloadDOUFile($date, $section, 'zip');
            }
        }
    }

    /**
     * @throws GuzzleException
     */
    protected function downloadDOUFile(string $date, string $section, string $type, bool $overwrite = false)
    {
        $filepath = $this->getStoragePath(append: "/$date/$section", withExtension: true, raw: true);

        if (!$overwrite && $this->isCached($date, $section, $type)) {
            return true;
        }

        $this->ensurePathExists($filepath);

        $resource = fopen($this->storage->path($filepath), 'w+');
        $stream = Utils::streamFor($resource);

        $url = $this->getDownloadUrl($date, $section);

        $this->client->get($url, [
            'save_to' => $stream,
            'cookies' => $this->cookieJar
        ]);

        return true;
    }

    public function getStoragePath(
        string $type = 'zip', string $append = '',
        bool $withExtension = false, bool $raw = false): string
    {
        if ($withExtension) {
            $extension = ".$type";

            if (strlen($append) && !Str::endsWith($append, $extension)) {
                $append .= $extension;
            }
        }

        $append = Str::start($append, '/');

        $path = "/dou/{$type}{$append}";

        return $raw ? $path : $this->storage->path($path);
    }

    public function isCached(string $date, ?string $section, ?string $type): bool
    {
        $withExtension = !empty($section);

        return $this->storage->exists(
            $this->getStoragePath(
                type: $type,
                append: $section ? "$date/$section" : $date,
                withExtension: $withExtension,
                raw: true
            )
        );
    }

    private function ensurePathExists(string $path): bool
    {
        $targetFolder = dirname($path);

        if (!$this->storage->exists($targetFolder)) {
            $this->storage->makeDirectory($targetFolder);
        }

        return true;
    }

    public function getDownloadUrl(string $date, string $section): string
    {
        $filename = $this->getFilename($date, $section);

        return self::URL_DOWNLOAD . "?p={$date}&dl={$filename}";
    }

    public function getFilename(string $date, string $section, string $type = 'zip'): string
    {
        return "{$date}-{$section}.{$type}";
    }
}

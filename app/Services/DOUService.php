<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;


class DOUService
{
    const URL_LOGIN = "logar.php";
    const URL_DOWNLOAD = "index.php";

    const DOU_SECTIONS = ['DO1', 'DO2', 'DO3', 'DO1E', 'DO2E', 'DO3E'];

    protected Client $client;
    protected CookieJar $cookieJar;

    public function __construct()
    {
        $this->client = $this->createClient();
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
            now()->addMinutes(10),
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
                $this->downloadDOUFile($date, $section);
            }
        }
    }

    /**
     * @throws GuzzleException
     */
    protected function downloadDOUFile(string $date, $section)
    {
        $storage = Storage::disk('local');

        $filename = "{$date}-{$section}.zip";
        $filepath = "dou/zip/$date/$section.zip";

        $targetFolder = dirname($filepath);

        if (!$storage->exists($targetFolder)) {
            $storage->makeDirectory($targetFolder);
        }

        dump($storage->path($filepath));
        $resource = fopen($storage->path($filepath), 'w+');
        $stream = Utils::streamFor($resource);

        $url = self::URL_DOWNLOAD . "?p={$date}&dl={$filename}";

        $this->client->get($url, [
            'save_to' => $stream,
            'cookies' => $this->cookieJar
        ]);
    }
}

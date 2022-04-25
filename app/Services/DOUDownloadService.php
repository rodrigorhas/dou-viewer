<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use voku\helper\HtmlDomParser;


class DOUDownloadService extends DOUService
{
    const URL_LOGIN = "logar.php";
    const URL_INDEX = "index.php";

    const DOU_SECTIONS = ['DO1', 'DO2', 'DO3', 'DO1E', 'DO2E', 'DO3E'];

    public Filesystem $storage;
    protected Client $client;
    protected CookieJar $cookieJar;

    public function __construct()
    {
        $this->client = $this->createClient();
        $this->storage = Storage::disk('local');

        parent::__construct();
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
        $dates = $this->todayIfEmpty($dates);

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

        $url = $this->getDownloadUrl($date, $section);

        $response = $this->client->get($url, [
            'cookies' => $this->cookieJar
        ]);

        $sucessfull = Arr::first($response->getHeader('content-type')) === 'application/octet-stream';

        if (!$sucessfull) {
            unlink($this->storage->path($filepath));

            return false;
        }

        $this->storage->put($filepath, $response->getBody());

        return true;
    }

    public function getDownloadUrl(string $date, string $section): string
    {
        $filename = $this->getFilename($date, $section);

        return self::URL_INDEX . "?p={$date}&dl={$filename}";
    }

    public function listRepositories ()
    {
        $html = Cache::remember('repository-list-html', now()->addMinutes(10), function () {
            $response = $this->client->get(self::URL_INDEX, [
                'cookies' => $this->cookieJar
            ]);

            return $response->getBody()->getContents();
        });

        $dom = HtmlDomParser::str_get_html($html);
        $rows = $dom->find('#main-table > tr');

        $baseUri = Config::get('inlabs.base_url');

        return collect($rows)
            ->map(function ($row) use ($baseUri) {
                $link = $row->find('a', 0);
                $columns = $row->find('td');

                $url = implode('/', [$baseUri, self::URL_INDEX . $link->href]);

                return [
                    'name' => $link->text(),
                    'size' => $columns[1]->text(),
                    'url' => $url,
                    'posted_at' => $columns[2]->text(),
                ];
            })
            ->toArray();
    }
}

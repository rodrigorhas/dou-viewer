<?php

namespace App\Console\Commands;

use App\Services\DOUDownloadService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class FetchDOU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "dou:fetch
       {dates?* : Dates to be fetched}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch DOU by given date';

    /**
     * Execute the console command.
     *
     * @throws GuzzleException
     */
    public function handle()
    {
        /** @var DOUDownloadService $douService */
        $douService = app(DOUDownloadService::class);

        $douService->login();
        $douService->download($this->argument('dates'));
    }
}

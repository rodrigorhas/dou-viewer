<?php

namespace App\Console\Commands;

use App\Services\DOUDownloadService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class ListDOU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dou:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available DOU repository';

    /**
     * Execute the console command.
     * @throws GuzzleException
     */
    public function handle()
    {
        /** @var DOUDownloadService $douService */
        $douService = app(DOUDownloadService::class);

        $douService->login();
        $repositories = $douService->listRepositories();

        $this->output->table(array_keys($repositories[0]), $repositories);
    }
}

<?php

namespace App\Console\Commands;

use App\Services\DOUParserService;
use App\Services\DOUService;
use Illuminate\Console\Command;

class ExtractImportDOU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "dou:extract-import
       {dates?* : Dates extracted and imported}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract and import DOU by given date';

    public function __construct(
        protected DOUParserService $parserService = new DOUParserService(),
        protected DOUService       $douService = new DOUService()
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $dates = $this->argument('dates');

        $dates = $this->douService->todayIfEmpty($dates);

        foreach ($dates as $date) {
            $this->recursiveParse($date);
        }
    }

    private function recursiveParse(string $date)
    {
        $dateFolderPath = $this->douService->getStoragePath(append: "$date");
        $sections = $this->parserService->getSectionsFromFolder($dateFolderPath);

        foreach ($sections as $section => $files) {
            foreach ($files as $file) {
                $parsed = $this->parserService->parseXML(
                    $this->douService->getStoragePath(
                        append: implode('/', [$date, $section, $file])
                    )
                );

                dump($parsed);
            }
        }
    }
}

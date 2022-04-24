<?php

namespace App\Console\Commands;

use App\Services\DOUParserService;
use Illuminate\Console\Command;

class ImportDOU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "dou:import
       {dates?* : Dates to be imported}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import DOU by given date';

    public function __construct(
        protected DOUParserService $parserService = new DOUParserService()
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

        $dates = $this->parserService->todayIfEmpty($dates);

        foreach ($dates as $date) {
            $this->recursiveParse($date);
        }
    }

    private function recursiveParse(string $date)
    {
        $dateFolderPath = $this->parserService->getStoragePath(append: "$date");
        $sections = $this->parserService->getSectionsFromFolder($dateFolderPath);

        foreach ($sections as $section => $files) {
            foreach ($files as $file) {
                $parsed = $this->parserService->parseXML(
                    $this->parserService->getStoragePath(
                        append: implode('/', [$date, $section, $file])
                    )
                );

                dump($parsed);
            }
        }
    }
}

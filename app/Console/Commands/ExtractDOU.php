<?php

namespace App\Console\Commands;

use App\Services\DOUExtractorService;
use Illuminate\Console\Command;

class ExtractDOU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "dou:extract
       {dates?* : Dates to be extracted}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract DOU by date';

    public function __construct(
        protected DOUExtractorService $extractorService = new DOUExtractorService(),
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle(): array
    {
        $dates = $this->argument('dates');

        $dates = $this->extractorService->todayIfEmpty($dates);

        $reports = [];

        foreach ($dates as $date) {
            $reports[$date] = $this->extractorService->extractFolder($date);
        }

        return $reports;
    }
}

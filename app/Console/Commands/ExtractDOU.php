<?php

namespace App\Console\Commands;

use App\Services\DOUParserService;
use DirectoryIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
        protected DOUParserService $parserService = new DOUParserService(),
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

        $dates = $this->parserService->todayIfEmpty($dates);

        $reports = [];

        foreach ($dates as $date) {
            $reports[$date] = $this->extractFolder($date);
        }

        return $reports;
    }

    private function extractFolder(string $date, bool $overwrite = false): array
    {
        $folderPath = $this->parserService->getStoragePath(append: $date);
        $iterator = new DirectoryIterator(
            $folderPath
        );

        $sections = [];

        /** @var DirectoryIterator $item */
        foreach ($iterator as $item) {
            if (Str::endsWith($item, '.zip')) {
                $sectionName = Str::before($item, '.zip');

                $section = [
                    'name' => $sectionName,
                    'file' => $item->getFilename(),
                    'folder' => $folderPath . "/$sectionName"
                ];

                if (!$overwrite && File::exists($section['folder'])) {
                    continue;
                }

                $section['extracted'] = $this->parserService->extractSectionZip($date, $sectionName);

                $sections[] = $section;
            }
        }

        return $sections;
    }
}

<?php

namespace App\Services;

use DirectoryIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class DOUExtractorService extends DOUService
{
    public function extractFolder(string $date, bool $overwrite = false): array
    {
        $folderPath = $this->getStoragePath(append: $date);
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

                $section['extracted'] = $this->extractSectionZip($date, $sectionName);

                $sections[] = $section;
            }
        }

        return $sections;
    }

    public function extractSectionZip(string $date, string $section): bool
    {
        $filepath = $this->getStoragePath(append: "/$date/$section", withExtension: true);

        $zip = new ZipArchive();

        if ($zip->open($filepath) === TRUE) {
            $zip->extractTo(Str::before($filepath, '.zip'));
            $zip->close();

            return true;
        } else {
            return false;
        }
    }
}

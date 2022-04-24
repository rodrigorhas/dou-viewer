<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;

class DOUParserService
{
    public function parseXML(string $filename): array
    {
        libxml_use_internal_errors(TRUE);

        $objXmlDocument = simplexml_load_file($filename, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($objXmlDocument === FALSE) {
            return [
                'errors' => array_map(fn ($error) => $error->message, libxml_get_errors())
            ];
        }

        $objJsonDocument = json_encode($objXmlDocument);

        return [
            'data' => json_decode($objJsonDocument, TRUE)
        ];
    }

    protected function recursiveIterateFilter(string $dateFolderPath, $callback): array
    {
        if (!File::exists($dateFolderPath)) {
            return [];
        }

        $dir = new RecursiveDirectoryIterator($dateFolderPath);
        $result = [];

        foreach ($dir as $item) {
            $callbackResult = $callback($item);

            if (!$callbackResult) {
                continue;
            }

            if (is_array($callbackResult) && isset($callbackResult[0]) && isset($callbackResult[1])) {
                [$key, $value] = $callbackResult;
                $result[$key] = $value;
                continue;
            }

            $result[] = $callbackResult;
        }

        return $result;
    }

    public function getSectionsFromFolder(string $path): array
    {
        return $this->recursiveIterateFilter($path, function ($sectionItem) use ($path) {
            $name = $sectionItem->getBasename();

            if (!$sectionItem->isDir() || Str::startsWith($name, '.')) {
                return false;
            }

            $section = $sectionItem->getFilename();

            $files = $this->recursiveIterateFilter($path . "/$section", function ($fileItem) {
                $name = $fileItem->getBasename();

                if ($fileItem->isDir() || Str::startsWith($name, '.')) {
                    return false;
                }

                if (!Str::endsWith($name, '.xml')) {
                    return false;
                }

                return $name;
            });

            return [
                $section, $files
            ];
        });
    }
}

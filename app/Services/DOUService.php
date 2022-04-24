<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class DOUService
{
    public Filesystem $storage;

    public function __construct()
    {
        $this->storage = Storage::disk('local');
    }

    public function todayIfEmpty(array $dates = [])
    {
        if (!count($dates)) {
            $dates[] = Carbon::now()->format('Y-m-d');
        }

        return $dates;
    }

    public function isCached(string $date, ?string $section, ?string $type): bool
    {
        $withExtension = !empty($section);

        return $this->storage->exists(
            $this->getStoragePath(
                type: $type,
                append: $section ? "$date/$section" : $date,
                withExtension: $withExtension,
                raw: true
            )
        );
    }

    public function getStoragePath(
        string $type = 'zip', string $append = '',
        bool   $withExtension = false, bool $raw = false): string
    {
        if ($withExtension) {
            $extension = ".$type";

            if (strlen($append) && !Str::endsWith($append, $extension)) {
                $append .= $extension;
            }
        }

        $append = Str::start($append, '/');

        $path = "/dou/{$type}{$append}";

        return $raw ? $path : $this->storage->path($path);
    }

    public function ensurePathExists(string $path): bool
    {
        $targetFolder = dirname($path);

        if (!$this->storage->exists($targetFolder)) {
            $this->storage->makeDirectory($targetFolder);
        }

        return true;
    }

    public function getFilename(string $date, string $section, string $type = 'zip'): string
    {
        return "{$date}-{$section}.{$type}";
    }
}

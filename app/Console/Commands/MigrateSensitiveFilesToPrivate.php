<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateSensitiveFilesToPrivate extends Command
{
    protected $signature = 'howsey:migrate-sensitive-files';

    protected $description = 'Move public agreements and archive exports into private storage.';

    public function handle(): int
    {
        $sourceDisk = Storage::disk('public');
        $targetDisk = Storage::disk('private');
        $movedCount = 0;
        $skippedCount = 0;

        foreach (['agreements', 'archives', 'maintenance-archives', 'tenant-evictions'] as $directory) {
            foreach ($sourceDisk->allFiles($directory) as $path) {
                if ($targetDisk->exists($path)) {
                    $sourceDisk->delete($path);
                    $skippedCount++;
                    continue;
                }

                $targetDisk->put($path, $sourceDisk->get($path));
                $sourceDisk->delete($path);
                $movedCount++;
            }
        }

        $this->info("Sensitive file migration complete. Moved: {$movedCount}. Removed duplicates: {$skippedCount}.");

        return self::SUCCESS;
    }
}

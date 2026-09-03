<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Blueprint §47 Scheduler names "Backup verification" as its own
 * automated item, distinct from taking the backup itself -- exactly
 * the "a backup that has never been restored is not a proven backup"
 * principle (§51 17.22), run on a schedule rather than trusted once.
 * Finds the most recent backup:run output and restores it into a
 * throwaway scratch directory, relying on RestoreBackup's own checksum
 * verification to catch corruption; the scratch restore is deleted
 * immediately after either way, since this command's job is proving
 * the backup restores cleanly, not producing a usable copy.
 */
class VerifyLatestBackup extends Command
{
    protected $signature = 'backup:verify-latest {--backups-root= : Defaults to storage/app/backups}';

    protected $description = 'Restore the most recent backup into a scratch directory to prove it actually restores, then discard the copy.';

    public function handle(): int
    {
        $backupsRoot = $this->option('backups-root') ?: storage_path('app/backups');
        $latest = collect(File::directories($backupsRoot))
            ->filter(fn ($dir) => File::exists($dir.'/manifest.json'))
            ->sortDesc()
            ->first();

        if (! $latest) {
            $this->error('No backups found to verify.');

            return self::FAILURE;
        }

        $scratchDir = storage_path('app/backups/.verify-scratch-'.now()->timestamp);

        $exitCode = Artisan::call('backup:restore', [
            'backup-dir' => $latest,
            'output-dir' => $scratchDir,
        ]);

        File::deleteDirectory($scratchDir);

        if ($exitCode !== self::SUCCESS) {
            $this->error("Backup verification FAILED for {$latest} -- see backup:restore output above.");

            return self::FAILURE;
        }

        $this->info("Backup verification passed for {$latest}.");

        return self::SUCCESS;
    }
}

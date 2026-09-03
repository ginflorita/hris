<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Decrypts a backup.run{@see RunBackup} output directory into a plain,
 * usable set of files at the given output path, verifying each
 * payload's checksum against the manifest recorded at backup time --
 * "a backup that has never been restored is not a proven backup"
 * (blueprint §51 17.22) means restore has to actually run, not just
 * exist as an idea.
 *
 * Deliberately does NOT swap the restored files into the live database
 * path or storage/app/private/ itself -- doing that safely needs the
 * application stopped first (a live SQLite file can't be safely
 * overwritten out from under an open connection, and MySQL needs the
 * app pointed at a maintenance window), which is a deployment
 * *procedure* decision, not something an automated command should
 * silently do. See the Disaster Recovery runbook (18d) for the actual
 * swap-in steps; this command's job ends at "here is the verified,
 * decrypted, restorable content."
 */
class RestoreBackup extends Command
{
    protected $signature = 'backup:restore {backup-dir : Directory containing manifest.json + the .enc files} {output-dir : Where to write the decrypted, restored files}';

    protected $description = 'Decrypt a backup and verify its checksums, writing the restored files to output-dir.';

    public function handle(): int
    {
        $backupDir = rtrim($this->argument('backup-dir'), '/');
        $outputDir = rtrim($this->argument('output-dir'), '/');

        $manifestPath = "{$backupDir}/manifest.json";
        if (! File::exists($manifestPath)) {
            $this->error("No manifest.json found in {$backupDir}");

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        File::ensureDirectoryExists($outputDir);

        try {
            foreach ($manifest['files'] as $key => $entry) {
                $raw = $this->decryptAndVerify($backupDir, $entry);

                if ($key === 'files') {
                    $this->extractFilesArchive($raw, $outputDir.'/files');
                } else {
                    File::put($outputDir.'/'.$entry['original'], $raw);
                }
            }
        } catch (RuntimeException|DecryptException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup restored and checksum-verified into {$outputDir}");

        return self::SUCCESS;
    }

    /**
     * @param  array{encrypted_filename: string, checksum: string, original: string}  $entry
     */
    private function decryptAndVerify(string $backupDir, array $entry): string
    {
        $encrypted = File::get("{$backupDir}/{$entry['encrypted_filename']}");
        $raw = Crypt::decryptString($encrypted);

        if (hash('sha256', $raw) !== $entry['checksum']) {
            throw new RuntimeException("Checksum mismatch restoring {$entry['original']} -- backup may be corrupted or tampered with.");
        }

        return $raw;
    }

    private function extractFilesArchive(string $zipBytes, string $destination): void
    {
        File::ensureDirectoryExists($destination);
        $zipPath = tempnam(sys_get_temp_dir(), 'hris-restore-files-').'.zip';
        File::put($zipPath, $zipBytes);

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $zip->extractTo($destination);
        $zip->close();

        File::delete($zipPath);
    }
}

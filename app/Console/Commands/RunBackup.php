<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Blueprint §51 17.22 / §54 Phase 18: "Database Backup + File Backup +
 * Configuration Backup," encrypted. Deliberately built as a small,
 * auditable command rather than pulling in a third-party backup
 * package this late in the project -- blueprint's actual ask (three
 * payloads, encrypted, checksummed, restorable) is narrow enough that
 * a bespoke command stays legible end to end, the same judgment call
 * this app already made for every other "collapse to what's real"
 * decision rather than reaching for a heavier dependency by default.
 *
 * Each of the three payloads is encrypted independently with Laravel's
 * own Crypt facade (AES-256-CBC with a MAC, keyed by APP_KEY) rather
 * than a separate backup-specific key -- whoever can decrypt/restore a
 * backup already needs equivalent access to the app's own environment
 * (the APP_KEY itself), so a second secret to manage would add
 * complexity without a real security boundary behind it.
 *
 * Reads whole payloads into memory before encrypting (Crypt::encryptString()
 * is not a streaming API). Fine at this app's scale; a genuinely large
 * production database would need a streaming approach (e.g. piping
 * mysqldump through `openssl enc`) instead -- a real, documented
 * follow-up, not built speculatively here.
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run
        {--output-dir= : Defaults to storage/app/backups/<timestamp>}
        {--source-dir= : Private files directory to archive; defaults to storage/app/private}
        {--env-path= : .env file to back up; defaults to the app\'s own}
        {--database-path= : SQLite file to back up; defaults to the configured connection (ignored for MySQL)}';

    protected $description = 'Create an encrypted database + private files + .env backup, checksummed for restore verification.';

    public function handle(): int
    {
        $timestamp = now()->format('Y-m-d_His');
        $outputDir = $this->option('output-dir') ?: storage_path("app/backups/{$timestamp}");
        File::ensureDirectoryExists($outputDir);

        $manifest = [
            'created_at' => now()->toIso8601String(),
            'db_connection' => config('database.default'),
            'files' => [],
        ];

        $manifest['files']['database'] = $this->backupDatabase($outputDir);
        $manifest['files']['files'] = $this->backupPrivateFiles($outputDir);
        $manifest['files']['env'] = $this->backupEnvironment($outputDir);

        File::put($outputDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $this->info("Backup written to {$outputDir}");

        return self::SUCCESS;
    }

    /**
     * @return array{encrypted_filename: string, checksum: string, original: string}
     */
    private function backupDatabase(string $outputDir): array
    {
        $connection = config('database.default');

        $raw = match ($connection) {
            'sqlite' => File::get($this->option('database-path') ?: config('database.connections.sqlite.database')),
            default => $this->dumpMysql(),
        };

        return $this->writeEncrypted($outputDir, 'database.enc', $raw, $connection === 'sqlite' ? 'database.sqlite' : 'database.sql');
    }

    private function dumpMysql(): string
    {
        $db = config('database.connections.mysql');

        $result = Process::timeout(300)->run([
            'mysqldump',
            '-h', $db['host'],
            '-P', (string) $db['port'],
            '-u', $db['username'],
            '--password='.$db['password'],
            $db['database'],
        ]);

        if ($result->failed()) {
            throw new \RuntimeException('mysqldump failed: '.$result->errorOutput());
        }

        return $result->output();
    }

    /**
     * @return array{encrypted_filename: string, checksum: string, original: string}
     */
    private function backupPrivateFiles(string $outputDir): array
    {
        $sourceDir = $this->option('source-dir') ?: storage_path('app/private');
        $zipPath = tempnam(sys_get_temp_dir(), 'hris-backup-files-').'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if (File::isDirectory($sourceDir)) {
            foreach (File::allFiles($sourceDir) as $file) {
                $zip->addFile($file->getPathname(), Str::after($file->getPathname(), $sourceDir.'/'));
            }
        }

        $zip->close();
        $raw = File::get($zipPath);
        File::delete($zipPath);

        return $this->writeEncrypted($outputDir, 'files.enc', $raw, 'files.zip');
    }

    /**
     * @return array{encrypted_filename: string, checksum: string, original: string}
     */
    private function backupEnvironment(string $outputDir): array
    {
        $envPath = $this->option('env-path') ?: base_path('.env');
        $raw = File::exists($envPath) ? File::get($envPath) : '';

        return $this->writeEncrypted($outputDir, 'env.enc', $raw, '.env');
    }

    /**
     * @return array{encrypted_filename: string, checksum: string, original: string}
     */
    private function writeEncrypted(string $outputDir, string $filename, string $raw, string $originalName): array
    {
        $checksum = hash('sha256', $raw);
        File::put($outputDir.'/'.$filename, Crypt::encryptString($raw));

        return ['encrypted_filename' => $filename, 'checksum' => $checksum, 'original' => $originalName];
    }
}

<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Blueprint §51 17.22: "A backup that has never been restored is not a
 * proven backup." These tests exercise the real backup:run/backup:restore
 * commands against real files on disk (a temp SQLite database, a temp
 * private-files directory, a temp .env) rather than mocking any part of
 * the pipeline -- encryption, checksum verification, and the zip
 * archive round-trip all run for real. The default paths (the app's own
 * configured SQLite connection, storage/app/private, base_path('.env'))
 * aren't used here since phpunit.xml runs the suite against an
 * in-memory (":memory:") SQLite connection with no file to back up --
 * a real deployment always uses a real file, per .env.example, so
 * that's not a gap in the command, just a reason to use its
 * --database-path/--source-dir/--env-path overrides for testing.
 */
class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = storage_path('framework/testing/backup-'.uniqid());
        File::ensureDirectoryExists($this->workDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);
        parent::tearDown();
    }

    private function makeSourceFixtures(): array
    {
        $dbPath = $this->workDir.'/source.sqlite';
        $pdo = new \PDO("sqlite:{$dbPath}");
        $pdo->exec('CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO widgets (name) VALUES ('alpha'), ('beta')");
        unset($pdo);

        $sourceFilesDir = $this->workDir.'/private';
        File::ensureDirectoryExists($sourceFilesDir.'/employee-documents/1');
        File::put($sourceFilesDir.'/employee-documents/1/resume.pdf', 'fake pdf bytes');

        $envPath = $this->workDir.'/fake.env';
        File::put($envPath, "APP_KEY=base64:test\nDB_PASSWORD=super-secret\n");

        return [$dbPath, $sourceFilesDir, $envPath];
    }

    public function test_a_full_backup_and_restore_round_trip_preserves_all_three_payloads(): void
    {
        [$dbPath, $sourceFilesDir, $envPath] = $this->makeSourceFixtures();
        $backupDir = $this->workDir.'/backup';
        $restoreDir = $this->workDir.'/restored';

        $this->artisan('backup:run', [
            '--output-dir' => $backupDir,
            '--database-path' => $dbPath,
            '--source-dir' => $sourceFilesDir,
            '--env-path' => $envPath,
        ])->assertSuccessful();

        $this->assertFileExists($backupDir.'/manifest.json');
        $this->assertFileExists($backupDir.'/database.enc');
        $this->assertFileExists($backupDir.'/files.enc');
        $this->assertFileExists($backupDir.'/env.enc');

        $this->artisan('backup:restore', ['backup-dir' => $backupDir, 'output-dir' => $restoreDir])
            ->assertSuccessful();

        // Database: byte-identical to the source, and genuinely queryable.
        $this->assertSame(File::get($dbPath), File::get($restoreDir.'/database.sqlite'));
        $pdo = new \PDO('sqlite:'.$restoreDir.'/database.sqlite');
        $names = $pdo->query('SELECT name FROM widgets ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['alpha', 'beta'], $names);

        // Files: the zip round-trip preserved the nested path and content.
        $this->assertSame(
            'fake pdf bytes',
            File::get($restoreDir.'/files/employee-documents/1/resume.pdf'),
        );

        // Configuration: the .env content round-tripped intact.
        $this->assertSame(File::get($envPath), File::get($restoreDir.'/.env'));
    }

    public function test_backup_files_are_actually_encrypted_not_just_copied(): void
    {
        [$dbPath, $sourceFilesDir, $envPath] = $this->makeSourceFixtures();
        $backupDir = $this->workDir.'/backup';

        $this->artisan('backup:run', [
            '--output-dir' => $backupDir,
            '--database-path' => $dbPath,
            '--source-dir' => $sourceFilesDir,
            '--env-path' => $envPath,
        ])->assertSuccessful();

        $this->assertStringNotContainsString('super-secret', File::get($backupDir.'/env.enc'));
        $this->assertStringNotContainsString('alpha', File::get($backupDir.'/database.enc'));

        // And it's genuinely Laravel's own encryption, not a bespoke scheme --
        // decryptable through the same Crypt facade a restore uses internally.
        $decrypted = Crypt::decryptString(File::get($backupDir.'/env.enc'));
        $this->assertStringContainsString('super-secret', $decrypted);
    }

    public function test_restore_rejects_a_tampered_backup_file(): void
    {
        [$dbPath, $sourceFilesDir, $envPath] = $this->makeSourceFixtures();
        $backupDir = $this->workDir.'/backup';
        $restoreDir = $this->workDir.'/restored';

        $this->artisan('backup:run', [
            '--output-dir' => $backupDir,
            '--database-path' => $dbPath,
            '--source-dir' => $sourceFilesDir,
            '--env-path' => $envPath,
        ])->assertSuccessful();

        // Swap in a *validly re-encrypted* but different payload -- proves
        // the checksum, not just "does it decrypt," is what's guarding
        // restore integrity.
        File::put($backupDir.'/env.enc', Crypt::encryptString('APP_KEY=tampered'));

        $this->artisan('backup:restore', ['backup-dir' => $backupDir, 'output-dir' => $restoreDir])
            ->assertFailed();
    }

    public function test_restore_reports_failure_when_no_manifest_exists(): void
    {
        $this->artisan('backup:restore', [
            'backup-dir' => $this->workDir.'/nonexistent',
            'output-dir' => $this->workDir.'/restored',
        ])->assertFailed();
    }

    public function test_verify_latest_restores_the_most_recent_backup_and_cleans_up_after_itself(): void
    {
        [$dbPath, $sourceFilesDir, $envPath] = $this->makeSourceFixtures();
        $backupsRoot = $this->workDir.'/backups';

        $this->artisan('backup:run', [
            '--output-dir' => $backupsRoot.'/2026-01-01_000000',
            '--database-path' => $dbPath,
            '--source-dir' => $sourceFilesDir,
            '--env-path' => $envPath,
        ])->assertSuccessful();

        $this->artisan('backup:verify-latest', ['--backups-root' => $backupsRoot])
            ->assertSuccessful();

        $this->assertCount(1, File::directories($backupsRoot), 'The scratch restore directory should have been deleted, leaving only the original backup.');
    }

    public function test_verify_latest_fails_when_no_backups_exist(): void
    {
        $emptyRoot = $this->workDir.'/empty-backups';
        File::ensureDirectoryExists($emptyRoot);

        $this->artisan('backup:verify-latest', ['--backups-root' => $emptyRoot])
            ->assertFailed();
    }

    public function test_verify_latest_fails_when_the_most_recent_backup_is_corrupted(): void
    {
        [$dbPath, $sourceFilesDir, $envPath] = $this->makeSourceFixtures();
        $backupsRoot = $this->workDir.'/backups';
        $backupDir = $backupsRoot.'/2026-01-01_000000';

        $this->artisan('backup:run', [
            '--output-dir' => $backupDir,
            '--database-path' => $dbPath,
            '--source-dir' => $sourceFilesDir,
            '--env-path' => $envPath,
        ])->assertSuccessful();

        File::put($backupDir.'/env.enc', Crypt::encryptString('tampered'));

        $this->artisan('backup:verify-latest', ['--backups-root' => $backupsRoot])
            ->assertFailed();
    }
}

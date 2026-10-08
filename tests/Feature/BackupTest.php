<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Uses a real SQLite file because in-memory databases cannot be copied.
 */
class BackupTest extends TestCase
{
    protected string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/subserp-backup-'.uniqid();
        File::ensureDirectoryExists($this->dir.'/storage/app');
        File::ensureDirectoryExists($this->dir.'/storage/framework/views');
        File::put($this->dir.'/database.sqlite', '');

        $this->app->useStoragePath($this->dir.'/storage');
        config(['database.connections.sqlite.database' => $this->dir.'/database.sqlite']);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_backup_can_be_created_and_restored(): void
    {
        $service = app(BackupService::class);
        User::factory()->create(['email' => 'before@example.com']);

        $name = $service->create();

        $this->assertTrue($service->isSqliteFile($service->path($name)));
        $this->assertCount(1, $service->all());

        User::factory()->create(['email' => 'after@example.com']);
        $this->assertSame(2, User::query()->count());

        $service->restore($name);

        $this->assertSame(['before@example.com'], User::query()->pluck('email')->all());
        // A safety copy is taken before restoring.
        $this->assertCount(2, $service->all());
    }

    public function test_admin_can_manage_backups_over_http(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('success');
        $backup = app(BackupService::class)->all()->first();

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee($backup['name']);
        $this->actingAs($admin)->get(route('admin.backups.download', $backup['name']))->assertOk()->assertDownload($backup['name']);
        $this->actingAs($admin)->get(route('admin.backups.download', '../../.env'))->assertNotFound();

        $this->actingAs($admin)->delete(route('admin.backups.destroy', $backup['name']))->assertSessionHas('success');
        $this->assertCount(0, app(BackupService::class)->all());
    }

    public function test_invalid_uploads_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('evil.sqlite', 'not a database');

        $this->actingAs($admin)
            ->post(route('admin.backups.upload'), ['file' => $file])
            ->assertSessionHas('error', 'The file is not a valid SQLite database.');

        $this->assertSame(1, User::query()->count());
    }

    public function test_old_backups_are_pruned(): void
    {
        $service = app(BackupService::class);

        foreach (range(1, 4) as $i) {
            File::put($service->directory()."/backup-2026010{$i}-000000-auto.sqlite", 'x');
            touch($service->directory()."/backup-2026010{$i}-000000-auto.sqlite", strtotime("2026-01-0{$i}"));
        }

        $this->assertSame(2, $service->prune(2));
        $this->assertSame(['backup-20260104-000000-auto.sqlite', 'backup-20260103-000000-auto.sqlite'], $service->all()->pluck('name')->all());
    }
}

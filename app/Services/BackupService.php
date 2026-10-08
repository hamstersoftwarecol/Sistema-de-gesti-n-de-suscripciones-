<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates, lists and restores copies of the SQLite database file.
 */
class BackupService
{
    public function directory(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function databasePath(): string
    {
        $connection = config('database.default');

        if (config("database.connections.{$connection}.driver") !== 'sqlite') {
            throw new RuntimeException(__('Backups are only available for SQLite databases.'));
        }

        return config("database.connections.{$connection}.database");
    }

    public function create(string $label = 'manual'): string
    {
        $source = $this->databasePath();
        $name = sprintf('backup-%s-%s.sqlite', now()->format('Ymd-His'), Str::slug($label) ?: 'manual');
        $target = $this->directory().DIRECTORY_SEPARATOR.$name;

        if ($source === ':memory:') {
            throw new RuntimeException(__('In-memory databases cannot be backed up.'));
        }

        // VACUUM INTO produces a consistent, compact copy even while the app is running.
        try {
            DB::statement('VACUUM INTO ?', [$target]);
        } catch (\Throwable) {
            File::copy($source, $target);
        }

        ActivityLog::record('backup', null, "Backup {$name}");

        return $name;
    }

    /** @return Collection<int, array{name: string, size: int, date: \Illuminate\Support\Carbon}> */
    public function all(): Collection
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($file) => $file->getExtension() === 'sqlite')
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'date' => \Illuminate\Support\Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('date')
            ->values();
    }

    public function path(string $name): string
    {
        $name = basename($name);
        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        if (! Str::endsWith($name, '.sqlite') || ! File::exists($path)) {
            throw new RuntimeException(__('Backup not found.'));
        }

        return $path;
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
    }

    /** Keep only the most recent N backups. */
    public function prune(int $keep): int
    {
        $deleted = 0;

        foreach ($this->all()->slice(max(1, $keep)) as $backup) {
            File::delete($this->directory().DIRECTORY_SEPARATOR.$backup['name']);
            $deleted++;
        }

        return $deleted;
    }

    public function restore(string $name): void
    {
        $this->restoreFromPath($this->path($name));
    }

    public function restoreFromUpload(UploadedFile $file): void
    {
        $this->restoreFromPath($file->getRealPath());
    }

    protected function restoreFromPath(string $path): void
    {
        if (! $this->isSqliteFile($path)) {
            throw new RuntimeException(__('The file is not a valid SQLite database.'));
        }

        $this->create('pre-restore');

        $target = $this->databasePath();
        $connection = config('database.default');

        DB::disconnect($connection);
        File::copy($path, $target);
        DB::reconnect($connection);

        // Bring the restored copy up to date with the current schema.
        Artisan::call('migrate', ['--force' => true]);

        Setting::flushCache();
        Cache::flush();
    }

    public function isSqliteFile(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if (! $handle) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        return $header === "SQLite format 3\0";
    }
}

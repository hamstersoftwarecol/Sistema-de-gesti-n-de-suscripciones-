<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateBackup extends Command
{
    protected $signature = 'backup:create {--label=auto} {--prune : Delete old backups beyond the configured retention}';

    protected $description = 'Create a copy of the SQLite database in storage/app/backups';

    public function handle(BackupService $backups): int
    {
        $name = $backups->create($this->option('label'));
        $this->info("Backup created: {$name}");

        if ($this->option('prune')) {
            $deleted = $backups->prune((int) Setting::get('backup_keep', 10));
            $this->info("Old backups deleted: {$deleted}");
        }

        return self::SUCCESS;
    }
}

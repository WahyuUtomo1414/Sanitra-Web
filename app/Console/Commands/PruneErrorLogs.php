<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use Illuminate\Console\Command;

class PruneErrorLogs extends Command
{
    protected $signature = 'error-log:prune {--days=60 : Hapus log lebih tua dari N hari}';

    protected $description = 'Hapus error log yang lebih tua dari batas hari yang ditentukan';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleted = ErrorLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} error log entries older than {$days} days.");

        return self::SUCCESS;
    }
}

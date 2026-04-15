<?php
// app/Console/Commands/RetryFailedImports.php

namespace App\Console\Commands;

use App\Models\ImportFailureLog;
use App\Jobs\RetryProductImport;
use Illuminate\Console\Command;

class RetryFailedImports extends Command
{
    protected $signature = 'import:retry-failed {--limit=100} {--import-id= : Specific import ID}';
    protected $description = 'Retry failed product imports';
    
    public function handle()
    {
        $limit = $this->option('limit');
        $importId = $this->option('import-id');
        
        $query = ImportFailureLog::where('resolved', false)
            ->where('retry_count', '<', 3)
            ->whereIn('error_type', ['api', 'network', 'rate_limit']);
        
        if ($importId) {
            $query->where('import_id', $importId);
        }
        
        $failures = $query->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
        
        if ($failures->isEmpty()) {
            $this->info("No failures found to retry");
            return;
        }
        
        $this->info("Found {$failures->count()} failures to retry");
        
        $bar = $this->output->createProgressBar($failures->count());
        
        foreach ($failures as $failure) {
            RetryProductImport::dispatch($failure->id);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("All failures have been queued for retry");
    }
}
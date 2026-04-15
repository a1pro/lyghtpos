<?php
// app/Console/Commands/CleanupOldImports.php

namespace App\Console\Commands;

use App\Models\MerchantProductImport;
use App\Models\ImportFailureLog;
use Illuminate\Console\Command;

class CleanupOldImports extends Command
{
    protected $signature = 'import:cleanup {--days=30} {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean up old import records';
    
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $date = now()->subDays($days);
        
        $this->info("Cleaning up imports older than {$days} days...");
        
        $oldImports = MerchantProductImport::where('created_at', '<', $date)
            ->whereIn('status', ['completed', 'failed', 'partial'])
            ->get();
        
        if ($oldImports->isEmpty()) {
            $this->info("No old imports found");
            return;
        }
        
        $this->info("Found {$oldImports->count()} old imports");
        
        if ($dryRun) {
            $this->warn("DRY RUN - No actual deletion will occur");
            foreach ($oldImports as $import) {
                $this->line("  - ID: {$import->id}, File: {$import->file_name}, Date: {$import->created_at->format('Y-m-d')}");
            }
            return;
        }
        
        $bar = $this->output->createProgressBar($oldImports->count());
        
        foreach ($oldImports as $import) {
            // Delete associated failures
            ImportFailureLog::where('import_id', $import->id)->delete();
            // Delete import record
            $import->delete();
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Cleanup completed");
    }
}
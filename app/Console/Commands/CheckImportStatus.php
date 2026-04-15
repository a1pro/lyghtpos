<?php
// app/Console/Commands/CheckImportStatus.php

namespace App\Console\Commands;

use App\Models\MerchantProductImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckImportStatus extends Command
{
    protected $signature = 'import:status {--id= : Check specific import} {--merchant= : Check imports for specific merchant}';
    protected $description = 'Check product import status';
    
    public function handle()
    {
        if ($id = $this->option('id')) {
            $this->checkSpecificImport($id);
            return;
        }
        
        if ($merchantId = $this->option('merchant')) {
            $this->checkMerchantImports($merchantId);
            return;
        }
        
        $this->showOverallStatus();
    }
    
    protected function showOverallStatus()
    {
        $this->info("\n═══════════════════════════════════════════════");
        $this->info("      PRODUCT IMPORT STATUS");
        $this->info("═══════════════════════════════════════════════\n");
        
        // Queue Status
        $jobs = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();
        
        $this->info("📊 QUEUE STATUS:");
        $this->line("   Jobs in queue: " . ($jobs ?: '0'));
        $this->line("   Failed jobs: " . ($failed ?: '0'));
        $this->line("");
        
        // Active Imports
        $activeImports = MerchantProductImport::whereIn('status', ['pending', 'processing'])->get();
        
        if ($activeImports->isNotEmpty()) {
            $this->info("🔄 ACTIVE IMPORTS:");
            foreach ($activeImports as $import) {
                $progress = $import->getProgressPercentage();
                $bar = $this->getProgressBar($progress, 20);
                $this->line("   ID: {$import->id} | {$import->file_name}");
                $this->line("   Status: {$import->status} | Progress: {$progress}% {$bar}");
                $this->line("   Success: {$import->success_rows} | Failed: {$import->failed_rows} | Total: {$import->total_rows}");
                $this->line("");
            }
        }
        
        // Recent Imports
        $recent = MerchantProductImport::latest()->take(5)->get();
        
        if ($recent->isNotEmpty()) {
            $this->info("📋 RECENT IMPORTS:");
            foreach ($recent as $import) {
                $icon = match($import->status) {
                    'completed' => '✅',
                    'processing' => '🔄',
                    'failed' => '❌',
                    'partial' => '⚠️',
                    default => '⏳'
                };
                $this->line("   {$icon} ID:{$import->id} | {$import->status} | {$import->success_rows}/{$import->total_rows}");
            }
        }
        
        $this->info("\n═══════════════════════════════════════════════\n");
    }
    
    protected function checkSpecificImport($id)
    {
        $import = MerchantProductImport::find($id);
        
        if (!$import) {
            $this->error("Import ID {$id} not found");
            return;
        }
        
        $this->info("\n═══════════════════════════════════════════════");
        $this->info("      IMPORT DETAILS - ID: {$id}");
        $this->info("═══════════════════════════════════════════════\n");
        
        $this->info("📄 FILE INFORMATION:");
        $this->line("   File: {$import->file_name}");
        $this->line("   Cyclos Import ID: {$import->cyclos_import_id}");
        $this->line("   Created: {$import->created_at}");
        $this->line("   Status: {$import->status}");
        
        $this->line("");
        $this->info("📊 PROGRESS:");
        
        $progress = $import->getProgressPercentage();
        $bar = $this->getProgressBar($progress, 30);
        $this->line("   {$bar} {$progress}%");
        $this->line("   Processed: {$import->processed_rows}/{$import->total_rows}");
        $this->line("   ✅ Success: {$import->success_rows}");
        $this->line("   ❌ Failed: {$import->failed_rows}");
        
        $this->info("\n═══════════════════════════════════════════════\n");
    }
    
    protected function checkMerchantImports($merchantId)
    {
        $imports = MerchantProductImport::where('user_id', $merchantId)
            ->latest()
            ->get();
        
        $this->info("\n═══════════════════════════════════════════════");
        $this->info("      IMPORTS FOR MERCHANT: {$merchantId}");
        $this->info("═══════════════════════════════════════════════\n");
        
        if ($imports->isEmpty()) {
            $this->line("No imports found for this merchant");
            return;
        }
        
        foreach ($imports as $import) {
            $this->line("ID: {$import->id} | {$import->file_name}");
            $this->line("   Status: {$import->status} | Progress: {$import->getProgressPercentage()}%");
            $this->line("   Success: {$import->success_rows} | Failed: {$import->failed_rows}");
            $this->line("");
        }
    }
    
    protected function getProgressBar($percent, $width = 20)
    {
        $filled = round($width * $percent / 100);
        $empty = $width - $filled;
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }
}
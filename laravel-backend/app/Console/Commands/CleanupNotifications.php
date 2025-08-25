<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsappNotification;
use Carbon\Carbon;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:cleanup-notifications {--days=30 : Number of days to keep notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old WhatsApp notifications from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up WhatsApp notifications older than {$days} days ({$cutoffDate->format('Y-m-d H:i:s')})...");
        
        try {
            // Count notifications to be deleted
            $count = WhatsappNotification::where('created_at', '<', $cutoffDate)->count();
            
            if ($count === 0) {
                $this->info('No old notifications found to clean up.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$count} notifications to clean up.");
            
            if ($this->confirm("Are you sure you want to delete {$count} old notifications?")) {
                // Delete old notifications
                $deleted = WhatsappNotification::where('created_at', '<', $cutoffDate)->delete();
                
                $this->info("✅ Successfully deleted {$deleted} old notifications.");
                
                // Display summary
                $remaining = WhatsappNotification::count();
                $this->info("📊 Remaining notifications in database: {$remaining}");
                
                return Command::SUCCESS;
            } else {
                $this->info('Cleanup cancelled.');
                return Command::SUCCESS;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
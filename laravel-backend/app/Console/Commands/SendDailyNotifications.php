<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\Settings;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class SendDailyNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:send-daily-notifications {--date= : Specific date to send notifications for (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily meeting notifications to WhatsApp group';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily notifications process...');
        
        $settings = Settings::getInstance();
        
        if (!$settings->group_notification_enabled) {
            $this->warn('Group notifications are disabled in settings');
            return Command::SUCCESS;
        }

        // Get date from option or use today
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        $this->info("Processing notifications for: {$date->format('Y-m-d')}");

        // Get meetings for the specified date
        $meetings = Meeting::whereDate('date', $date)
                          ->where('group_notification_enabled', true)
                          ->orderBy('start_time')
                          ->get();

        $this->info("Found {$meetings->count()} meetings for notification");

        if ($meetings->isEmpty()) {
            $this->info('No meetings found for group notification');
            return Command::SUCCESS;
        }

        try {
            $whatsappService = app(WhatsAppService::class);
            
            // Test connection first
            if (!$whatsappService->testConnection()) {
                $this->error('WhatsApp connection failed. Please check your configuration.');
                return Command::FAILURE;
            }

            $this->info('WhatsApp connection successful. Sending notifications...');
            
            // Send group notification
            $whatsappService->sendGroupNotification($meetings, $date->toDateString());
            
            // Update sent timestamp for meetings
            Meeting::whereDate('date', $date)
                   ->where('group_notification_enabled', true)
                   ->update(['group_notification_sent_at' => now()]);

            $this->info('✅ Daily notifications sent successfully');
            
            // Display summary
            $this->table(
                ['Meeting', 'Time', 'Location', 'Attendee'],
                $meetings->map(function ($meeting) {
                    return [
                        $meeting->title,
                        $meeting->start_time->format('H:i') . ' - ' . $meeting->end_time->format('H:i'),
                        $meeting->location,
                        $meeting->designated_attendee
                    ];
                })->toArray()
            );

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to send daily notifications: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
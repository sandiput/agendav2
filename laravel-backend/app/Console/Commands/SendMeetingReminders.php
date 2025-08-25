<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\Settings;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class SendMeetingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:send-reminders {--force : Force send reminders even if already sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send meeting reminders to designated attendees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting meeting reminders process...');
        
        $settings = Settings::getInstance();
        
        if (!$settings->individual_reminder_enabled) {
            $this->warn('Individual reminders are disabled in settings');
            return Command::SUCCESS;
        }

        $reminderMinutes = $settings->individual_reminder_minutes;
        $this->info("Reminder time: {$reminderMinutes} minutes before meeting");

        // Calculate the time window for reminders
        $now = now();
        $reminderWindowStart = $now->copy();
        $reminderWindowEnd = $now->copy()->addMinutes(5); // 5-minute window

        $this->info("Checking meetings between {$reminderWindowStart->format('Y-m-d H:i')} and {$reminderWindowEnd->format('Y-m-d H:i')}");

        // Get meetings that need reminders
        $query = Meeting::where('whatsapp_reminder_enabled', true)
                        ->with('participant');

        // Add force option check
        if (!$this->option('force')) {
            $query->whereNull('reminder_sent_at');
        }

        $meetings = $query->get()->filter(function ($meeting) use ($reminderMinutes, $reminderWindowStart, $reminderWindowEnd) {
            $meetingDateTime = Carbon::parse($meeting->date->format('Y-m-d') . ' ' . $meeting->start_time->format('H:i:s'));
            $reminderTime = $meetingDateTime->copy()->subMinutes($reminderMinutes);
            
            return $reminderTime->between($reminderWindowStart, $reminderWindowEnd);
        });

        $this->info("Found {$meetings->count()} meetings requiring reminders");

        if ($meetings->isEmpty()) {
            $this->info('No meetings require reminders at this time');
            return Command::SUCCESS;
        }

        $whatsappService = app(WhatsAppService::class);
        $successCount = 0;
        $failureCount = 0;

        // Test connection first
        if (!$whatsappService->testConnection()) {
            $this->error('WhatsApp connection failed. Please check your configuration.');
            return Command::FAILURE;
        }

        $this->info('WhatsApp connection successful. Sending reminders...');

        foreach ($meetings as $meeting) {
            try {
                if (!$meeting->participant) {
                    $this->warn("No participant found for meeting: {$meeting->title}");
                    $failureCount++;
                    continue;
                }

                $this->info("Sending reminder for: {$meeting->title} to {$meeting->participant->name}");
                
                $whatsappService->sendMeetingReminder($meeting, $meeting->participant);
                $meeting->update(['reminder_sent_at' => now()]);
                
                $this->info("✅ Reminder sent successfully");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to send reminder for meeting {$meeting->title}: " . $e->getMessage());
                $failureCount++;
            }
        }

        // Display summary
        $this->info("\n📊 Summary:");
        $this->info("✅ Successful: {$successCount}");
        $this->info("❌ Failed: {$failureCount}");
        $this->info("📱 Total processed: " . ($successCount + $failureCount));

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\Participant;
use App\Models\WhatsappNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class GenerateReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:generate-reports {--period=monthly : Report period (daily, weekly, monthly, yearly)} {--format=json : Output format (json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate meeting reports for specified period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('period');
        $format = $this->option('format');
        
        $this->info("Generating {$period} report in {$format} format...");
        
        try {
            $dateRange = $this->getDateRange($period);
            $reportData = $this->generateReportData($dateRange, $period);
            
            $filename = $this->saveReport($reportData, $period, $format);
            
            $this->info("✅ Report generated successfully: {$filename}");
            $this->displayReportSummary($reportData);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Report generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'daily':
                return [$now->startOfDay(), $now->endOfDay()];
            case 'weekly':
                return [$now->startOfWeek(), $now->endOfWeek()];
            case 'monthly':
                return [$now->startOfMonth(), $now->endOfMonth()];
            case 'yearly':
                return [$now->startOfYear(), $now->endOfYear()];
            default:
                return [$now->startOfMonth(), $now->endOfMonth()];
        }
    }

    private function generateReportData($dateRange, $period)
    {
        [$startDate, $endDate] = $dateRange;
        
        // Basic statistics
        $totalMeetings = Meeting::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count();
        $completedMeetings = Meeting::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereRaw('CONCAT(date, " ", end_time) < ?', [now()])
            ->count();
        
        // Participant statistics
        $activeParticipants = Participant::where('is_active', true)->count();
        $participantStats = Participant::selectRaw('seksi, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('seksi')
            ->get();
        
        // WhatsApp statistics
        $whatsappStats = WhatsappNotification::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        
        // Meeting details
        $meetings = Meeting::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('participant')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
        
        return [
            'report_info' => [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'generated_at' => now()->toISOString(),
            ],
            'summary' => [
                'total_meetings' => $totalMeetings,
                'completed_meetings' => $completedMeetings,
                'completion_rate' => $totalMeetings > 0 ? round(($completedMeetings / $totalMeetings) * 100, 2) : 0,
                'active_participants' => $activeParticipants,
            ],
            'participant_stats' => $participantStats->toArray(),
            'whatsapp_stats' => $whatsappStats->toArray(),
            'meetings' => $meetings->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'date' => $meeting->date->toDateString(),
                    'start_time' => $meeting->start_time->format('H:i'),
                    'end_time' => $meeting->end_time->format('H:i'),
                    'location' => $meeting->location,
                    'designated_attendee' => $meeting->designated_attendee,
                    'whatsapp_reminder_enabled' => $meeting->whatsapp_reminder_enabled,
                    'group_notification_enabled' => $meeting->group_notification_enabled,
                    'reminder_sent' => $meeting->reminder_sent_at ? true : false,
                    'group_notification_sent' => $meeting->group_notification_sent_at ? true : false,
                ];
            })->toArray(),
        ];
    }

    private function saveReport($data, $period, $format)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "reports/meeting-report-{$period}-{$timestamp}.{$format}";
        
        if ($format === 'json') {
            $content = json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            $content = $this->convertToCSV($data);
        } else {
            throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
        
        Storage::put($filename, $content);
        
        return $filename;
    }

    private function convertToCSV($data)
    {
        $csv = "Meeting Report - {$data['report_info']['period']}\n";
        $csv .= "Generated: {$data['report_info']['generated_at']}\n";
        $csv .= "Period: {$data['report_info']['start_date']} to {$data['report_info']['end_date']}\n\n";
        
        // Summary
        $csv .= "SUMMARY\n";
        $csv .= "Total Meetings,{$data['summary']['total_meetings']}\n";
        $csv .= "Completed Meetings,{$data['summary']['completed_meetings']}\n";
        $csv .= "Completion Rate,{$data['summary']['completion_rate']}%\n";
        $csv .= "Active Participants,{$data['summary']['active_participants']}\n\n";
        
        // Meetings
        $csv .= "MEETINGS\n";
        $csv .= "ID,Title,Date,Start Time,End Time,Location,Attendee,WhatsApp Reminder,Group Notification\n";
        
        foreach ($data['meetings'] as $meeting) {
            $csv .= implode(',', [
                $meeting['id'],
                '"' . str_replace('"', '""', $meeting['title']) . '"',
                $meeting['date'],
                $meeting['start_time'],
                $meeting['end_time'],
                '"' . str_replace('"', '""', $meeting['location']) . '"',
                '"' . str_replace('"', '""', $meeting['designated_attendee']) . '"',
                $meeting['whatsapp_reminder_enabled'] ? 'Yes' : 'No',
                $meeting['group_notification_enabled'] ? 'Yes' : 'No',
            ]) . "\n";
        }
        
        return $csv;
    }

    private function displayReportSummary($data)
    {
        $this->info("\n📊 Report Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', $data['report_info']['period']],
                ['Date Range', $data['report_info']['start_date'] . ' to ' . $data['report_info']['end_date']],
                ['Total Meetings', $data['summary']['total_meetings']],
                ['Completed Meetings', $data['summary']['completed_meetings']],
                ['Completion Rate', $data['summary']['completion_rate'] . '%'],
                ['Active Participants', $data['summary']['active_participants']],
            ]
        );
    }
}
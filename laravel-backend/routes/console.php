<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Meeting Manager specific commands
Artisan::command('meeting:status', function () {
    $this->info('Meeting Manager Status Check');
    $this->info('==========================');
    
    // Check database connection
    try {
        DB::connection()->getPdo();
        $this->info('✅ Database: Connected');
    } catch (\Exception $e) {
        $this->error('❌ Database: Failed - ' . $e->getMessage());
    }
    
    // Check settings
    try {
        $settings = \App\Models\Settings::getInstance();
        $this->info('✅ Settings: Loaded');
        $this->info("   - Group notifications: " . ($settings->group_notification_enabled ? 'Enabled' : 'Disabled'));
        $this->info("   - Individual reminders: " . ($settings->individual_reminder_enabled ? 'Enabled' : 'Disabled'));
        $this->info("   - WhatsApp connected: " . ($settings->whatsapp_connected ? 'Yes' : 'No'));
    } catch (\Exception $e) {
        $this->error('❌ Settings: Failed - ' . $e->getMessage());
    }
    
    // Check participants
    try {
        $participantCount = \App\Models\Participant::where('is_active', true)->count();
        $this->info("✅ Participants: {$participantCount} active");
    } catch (\Exception $e) {
        $this->error('❌ Participants: Failed - ' . $e->getMessage());
    }
    
    // Check meetings
    try {
        $upcomingCount = \App\Models\Meeting::upcoming()->count();
        $totalCount = \App\Models\Meeting::count();
        $this->info("✅ Meetings: {$totalCount} total, {$upcomingCount} upcoming");
    } catch (\Exception $e) {
        $this->error('❌ Meetings: Failed - ' . $e->getMessage());
    }
    
    // Check WhatsApp
    try {
        $whatsappService = app(\App\Services\WhatsAppService::class);
        $connected = $whatsappService->testConnection();
        $this->info('✅ WhatsApp: ' . ($connected ? 'Connected' : 'Disconnected'));
    } catch (\Exception $e) {
        $this->error('❌ WhatsApp: Failed - ' . $e->getMessage());
    }
    
})->purpose('Check Meeting Manager system status');

Artisan::command('meeting:demo-data', function () {
    $this->info('Creating demo data for Meeting Manager...');
    
    // Create demo participants
    $participants = [
        ['name' => 'Ahmad Wijaya', 'whatsapp_number' => '+6281234567890', 'nip' => '198501012010011001', 'seksi' => 'Intelijen Kepabeanan I'],
        ['name' => 'Siti Nurhaliza', 'whatsapp_number' => '+6281234567891', 'nip' => '198502022010012002', 'seksi' => 'Intelijen Kepabeanan II'],
        ['name' => 'Budi Santoso', 'whatsapp_number' => '+6281234567892', 'nip' => '198503032010013003', 'seksi' => 'Intelijen Cukai'],
        ['name' => 'Dewi Sartika', 'whatsapp_number' => '+6281234567893', 'nip' => '198504042010014004', 'seksi' => 'Dukungan Operasi Intelijen'],
    ];
    
    foreach ($participants as $participant) {
        \App\Models\Participant::updateOrCreate(
            ['nip' => $participant['nip']],
            $participant
        );
    }
    
    $this->info('✅ Demo participants created');
    
    // Create demo meetings
    $meetings = [
        [
            'title' => 'Rapat Koordinasi Bulanan',
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat Utama',
            'designated_attendee' => 'Ahmad Wijaya',
            'discussion_results' => 'Pembahasan program kerja bulan depan dan evaluasi kinerja tim.',
        ],
        [
            'title' => 'Briefing Intelijen Mingguan',
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:30',
            'location' => 'Ruang Briefing',
            'designated_attendee' => 'Siti Nurhaliza',
            'discussion_results' => 'Update situasi terkini dan strategi operasional.',
        ],
        [
            'title' => 'Workshop Teknologi Baru',
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'location' => 'Aula Pelatihan',
            'designated_attendee' => 'Budi Santoso',
            'dress_code' => 'Business Casual',
            'discussion_results' => 'Pelatihan penggunaan sistem baru untuk meningkatkan efisiensi kerja.',
        ],
    ];
    
    foreach ($meetings as $meeting) {
        \App\Models\Meeting::create($meeting);
    }
    
    $this->info('✅ Demo meetings created');
    $this->info('🎉 Demo data setup completed!');
    
})->purpose('Create demo data for testing');
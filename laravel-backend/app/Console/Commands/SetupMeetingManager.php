<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Settings;
use App\Models\Participant;
use Illuminate\Support\Facades\Hash;

class SetupMeetingManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:setup {--force : Force setup even if already configured}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Meeting Manager application with default data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up Meeting Manager...');

        // Check if already setup
        if (!$this->option('force') && User::exists()) {
            $this->warn('Meeting Manager appears to be already setup.');
            if (!$this->confirm('Do you want to continue anyway?')) {
                return Command::SUCCESS;
            }
        }

        try {
            // Setup admin user
            $this->setupAdminUser();
            
            // Setup default settings
            $this->setupDefaultSettings();
            
            // Setup sample participants
            $this->setupSampleParticipants();
            
            $this->info('✅ Meeting Manager setup completed successfully!');
            $this->displaySetupSummary();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function setupAdminUser()
    {
        $this->info('👤 Setting up admin user...');
        
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@meetingmanager.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );
        
        $this->info('✅ Admin user created/updated');
    }

    private function setupDefaultSettings()
    {
        $this->info('⚙️ Setting up default settings...');
        
        $settings = Settings::getInstance();
        $settings->update([
            'group_notification_time' => '07:00:00',
            'group_notification_enabled' => true,
            'individual_reminder_minutes' => 30,
            'individual_reminder_enabled' => true,
            'whatsapp_connected' => false,
        ]);
        
        $this->info('✅ Default settings configured');
    }

    private function setupSampleParticipants()
    {
        $this->info('👥 Setting up sample participants...');
        
        $sampleParticipants = [
            [
                'name' => 'Ahmad Wijaya',
                'whatsapp_number' => '+6281234567890',
                'nip' => '198501012010011001',
                'seksi' => 'Intelijen Kepabeanan I',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'whatsapp_number' => '+6281234567891',
                'nip' => '198502022010012002',
                'seksi' => 'Intelijen Kepabeanan II',
            ],
            [
                'name' => 'Budi Santoso',
                'whatsapp_number' => '+6281234567892',
                'nip' => '198503032010013003',
                'seksi' => 'Intelijen Cukai',
            ],
            [
                'name' => 'Dewi Sartika',
                'whatsapp_number' => '+6281234567893',
                'nip' => '198504042010014004',
                'seksi' => 'Dukungan Operasi Intelijen',
            ],
        ];

        foreach ($sampleParticipants as $participantData) {
            Participant::updateOrCreate(
                ['nip' => $participantData['nip']],
                $participantData
            );
        }
        
        $this->info('✅ Sample participants created');
    }

    private function displaySetupSummary()
    {
        $this->info("\n📋 Setup Summary:");
        $this->table(
            ['Component', 'Status', 'Details'],
            [
                ['Admin User', '✅ Ready', 'Username: admin, Password: admin123'],
                ['Database', '✅ Ready', 'All tables created with sample data'],
                ['Settings', '✅ Ready', 'Default notification settings configured'],
                ['Participants', '✅ Ready', count(Participant::all()) . ' participants created'],
                ['WhatsApp', '⚠️ Pending', 'Configure API credentials in .env file'],
            ]
        );

        $this->info("\n🔧 Next Steps:");
        $this->info("1. Configure WhatsApp Business API credentials in .env file");
        $this->info("2. Run: php artisan whatsapp:test-connection");
        $this->info("3. Setup queue workers: php artisan queue:work");
        $this->info("4. Setup cron job for scheduled notifications");
        $this->info("5. Access admin panel with username: admin, password: admin123");
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Settings;
use App\Models\Participant;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@meetingmanager.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Create default settings
        Settings::updateOrCreate(
            ['id' => 1],
            [
                'group_notification_time' => '07:00:00',
                'group_notification_enabled' => true,
                'individual_reminder_minutes' => 30,
                'individual_reminder_enabled' => true,
                'whatsapp_connected' => false,
            ]
        );

        // Create sample participants
        $participants = [
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

        foreach ($participants as $participant) {
            Participant::updateOrCreate(
                ['nip' => $participant['nip']],
                $participant
            );
        }

        $this->command->info('Database seeded successfully!');
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;
use App\Models\Settings;

class TestWhatsAppConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp Business API connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing WhatsApp Business API connection...');
        
        try {
            $whatsappService = app(WhatsAppService::class);
            
            // Display current configuration
            $this->info('Current Configuration:');
            $this->table(
                ['Setting', 'Value', 'Status'],
                [
                    ['Access Token', config('services.whatsapp.access_token') ? 'Set (' . substr(config('services.whatsapp.access_token'), 0, 10) . '...)' : 'Not Set', config('services.whatsapp.access_token') ? '✅' : '❌'],
                    ['Phone Number ID', config('services.whatsapp.phone_number_id') ?: 'Not Set', config('services.whatsapp.phone_number_id') ? '✅' : '❌'],
                    ['Business Account ID', config('services.whatsapp.business_account_id') ?: 'Not Set', config('services.whatsapp.business_account_id') ? '✅' : '❌'],
                    ['Webhook Verify Token', config('services.whatsapp.webhook_verify_token') ? 'Set' : 'Not Set', config('services.whatsapp.webhook_verify_token') ? '✅' : '❌'],
                    ['Group Number', config('services.whatsapp.group_number') ?: 'Not Set', config('services.whatsapp.group_number') ? '✅' : '❌'],
                ]
            );

            // Test connection
            $this->info('Testing API connection...');
            $connected = $whatsappService->testConnection();
            
            if ($connected) {
                $this->info('✅ WhatsApp Business API connection successful!');
                
                // Update settings
                $settings = Settings::getInstance();
                $settings->update(['whatsapp_connected' => true]);
                
                $this->info('✅ Connection status updated in database');
                
                return Command::SUCCESS;
            } else {
                $this->error('❌ WhatsApp Business API connection failed!');
                
                // Update settings
                $settings = Settings::getInstance();
                $settings->update(['whatsapp_connected' => false]);
                
                $this->error('❌ Connection status updated in database');
                
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Connection test failed with error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            // Update settings
            $settings = Settings::getInstance();
            $settings->update(['whatsapp_connected' => false]);
            
            return Command::FAILURE;
        }
    }
}
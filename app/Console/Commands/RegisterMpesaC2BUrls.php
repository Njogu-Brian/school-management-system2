<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentGateways\MpesaGateway;

class RegisterMpesaC2BUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpesa:register-c2b-urls 
                            {--confirmation-url= : Custom confirmation URL}
                            {--validation-url= : Custom validation URL}
                            {--response-type=Completed : Response type (Completed or Cancelled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register C2B validation and confirmation URLs with M-PESA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔗 Registering M-PESA C2B URLs...');
        $this->newLine();

        $gateway = app(MpesaGateway::class);

        // Get URLs from options or use defaults from config
        // Note: Use route without "mpesa" in path (Safaricom requirement)
        $confirmationUrl = $this->option('confirmation-url') 
            ?? config('mpesa.confirmation_url')
            ?? route('payment.webhook.c2b');
        
        $validationUrl = $this->option('validation-url') 
            ?? config('mpesa.validation_url')
            ?? route('payment.webhook.c2b');
        
        $responseType = $this->option('response-type') 
            ?? config('mpesa.c2b.response_type', 'Completed');

        // Validate response type
        if (!in_array($responseType, ['Completed', 'Cancelled'])) {
            $this->error('Response type must be either "Completed" or "Cancelled"');
            return Command::FAILURE;
        }

        // Display what will be registered
        $this->line('<options=bold>Registration Details:</>');
        $this->line('  <fg=blue>Environment:</> ' . strtoupper(config('mpesa.environment', 'production')));
        $this->line('  <fg=blue>Shortcode:</> ' . config('mpesa.shortcode', 'N/A'));
        $this->line('  <fg=blue>Confirmation URL:</> ' . $confirmationUrl);
        $this->line('  <fg=blue>Validation URL:</> ' . $validationUrl);
        $this->line('  <fg=blue>Response Type:</> ' . $responseType);
        $this->newLine();

        // Confirm registration
        if (!$this->confirm('Do you want to proceed with registration?', true)) {
            $this->info('Registration cancelled.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('⏳ Registering URLs with M-PESA...');
        $this->newLine();

        // Register URLs
        $result = $gateway->registerC2BUrls(
            $confirmationUrl,
            $validationUrl,
            $responseType
        );

        if ($result['success']) {
            // Check if URLs were already registered
            if (isset($result['already_registered']) && $result['already_registered']) {
                $this->info('✅ C2B URLs are already registered!');
                $this->newLine();
                $this->line('<fg=cyan>Note:</> In production, M-PESA only allows one URL registration per shortcode.');
                $this->line('The "already registered" message means your URLs are active and working.');
                $this->newLine();
            } else {
                $this->info('✅ C2B URLs registered successfully!');
                $this->newLine();
                
                $this->line('<options=bold>Registration Response:</>');
                $this->line('  <fg=green>Originator Conversation ID:</> ' . ($result['originator_conversation_id'] ?? 'N/A'));
                
                if (isset($result['response'])) {
                    $this->line('  <fg=green>Response Code:</> ' . ($result['response']['ResponseCode'] ?? 'N/A'));
                    $this->line('  <fg=green>Response Description:</> ' . ($result['response']['ResponseDescription'] ?? 'N/A'));
                }
                $this->newLine();
            }
            
            $this->line('<fg=yellow>Next Steps:</>');
            $this->line('  1. Verify registration at: https://developer.safaricom.co.ke/dashboard/urlmanagement');
            $this->line('  2. Make a test payment to your paybill');
            $this->line('  3. Check the C2B Dashboard to see if the transaction appears');
            $this->newLine();
            $this->line('<fg=cyan>To change URLs:</> Delete existing URLs first via the Daraja Portal, then re-register.');
            
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to register C2B URLs');
            $this->newLine();
            
            if (isset($result['message'])) {
                $this->line('<fg=red>Error:</> ' . $result['message']);
            }
            
            if (isset($result['error'])) {
                $this->line('<options=bold>Error Details:</>');
                if (is_array($result['error'])) {
                    $this->line(json_encode($result['error'], JSON_PRETTY_PRINT));
                } else {
                    $this->line($result['error']);
                }
            }
            
            $this->newLine();
            $this->line('<options=bold>Troubleshooting Tips:</>');
            $this->line('  1. Check your M-PESA credentials are correct');
            $this->line('  2. Ensure your IP is whitelisted (production only)');
            $this->line('  3. Verify URLs are publicly accessible');
            $this->line('  4. Check if URLs are already registered (delete first if needed)');
            $this->line('  5. Run: php artisan mpesa:test-credentials');
            
            return Command::FAILURE;
        }
    }
}

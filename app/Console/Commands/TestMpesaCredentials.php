<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentGateways\MpesaGateway;

class TestMpesaCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpesa:test-credentials {--clear-cache : Clear cached access token before testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test M-PESA API credentials and connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing M-PESA Credentials...');
        $this->newLine();

        $gateway = app(MpesaGateway::class);

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $gateway->clearAccessToken();
            $this->info('✓ Cleared cached access token');
            $this->newLine();
        }

        // Test credentials
        $results = $gateway->testCredentials();

        // Display environment
        $this->line('<fg=blue>Environment:</> ' . strtoupper($results['environment']));
        $this->newLine();

        // Display configuration status
        $this->line('<options=bold>Configuration Check:</>');
        if ($results['configured']) {
            $this->line('  ✓ <fg=green>All credentials configured</>');
            $this->line('    - Consumer Key Length: ' . ($results['details']['consumer_key_length'] ?? 'N/A'));
            $this->line('    - Consumer Secret Length: ' . ($results['details']['consumer_secret_length'] ?? 'N/A'));
            $this->line('    - Shortcode: ' . ($results['details']['shortcode'] ?? 'N/A'));
        } else {
            $this->line('  ✗ <fg=red>Missing credentials</>');
            foreach ($results['details'] as $key => $value) {
                $status = $value ? '✓' : '✗';
                $color = $value ? 'green' : 'red';
                $this->line("    {$status} <fg={$color}>{$key}: " . ($value ? 'Yes' : 'No') . "</>");
            }
        }
        $this->newLine();

        // Display authentication status
        $this->line('<options=bold>Authentication Test:</>');
        if ($results['token_obtained']) {
            $this->line('  ✓ <fg=green>Successfully obtained access token</>');
            $this->line('    - Token Length: ' . ($results['details']['token_length'] ?? 'N/A'));
            $this->line('    - Token Preview: ' . ($results['details']['token_preview'] ?? 'N/A'));
        } else {
            $this->line('  ✗ <fg=red>Failed to obtain access token</>');
        }
        $this->newLine();

        // Display errors
        if (!empty($results['errors'])) {
            $this->line('<options=bold>Errors:</>');
            foreach ($results['errors'] as $error) {
                $this->line('  • <fg=red>' . $error . '</>');
            }
            $this->newLine();
        }

        // Overall status
        if ($results['credentials_valid'] && $results['token_obtained']) {
            $this->info('✅ M-PESA credentials are VALID and working!');
            return Command::SUCCESS;
        } else {
            $this->error('❌ M-PESA credentials test FAILED');
            $this->newLine();
            
            // Troubleshooting tips
            $this->line('<options=bold>Troubleshooting Tips:</>');
            $this->line('  1. Check your .env file for correct M-PESA credentials');
            $this->line('  2. Ensure MPESA_ENVIRONMENT is set to "production" for live credentials');
            $this->line('  3. Verify Consumer Key and Secret from Daraja Portal');
            $this->line('  4. Make sure your IP is whitelisted (production only)');
            $this->line('  5. Check if the credentials are for the correct environment');
            $this->newLine();
            $this->line('<fg=yellow>Run: php artisan config:clear && php artisan cache:clear</>');
            
            return Command::FAILURE;
        }
    }
}

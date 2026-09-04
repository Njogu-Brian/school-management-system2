<?php

namespace App\Console\Commands;

use App\Services\ParentCredentialsService;
use Illuminate\Console\Command;

class SendParentCredentials extends Command
{
    protected $signature = 'parents:send-credentials
        {--channel=whatsapp : sms, whatsapp, or email}
        {--dry-run : Count families without sending}';

    protected $description = 'Send parent app username and password (no ERP link) to all families with active children';

    public function handle(ParentCredentialsService $credentials): int
    {
        $channel = strtolower((string) $this->option('channel'));
        if (! in_array($channel, ['sms', 'whatsapp', 'email'], true)) {
            $this->error('Channel must be sms, whatsapp, or email.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $credentials->shareToAllParents([$channel], $dryRun);

        $this->info(($dryRun ? 'Would send to ' : 'Sent to ').$result['ok'].' family/families via '.$channel.'.');
        if ($result['skipped'] > 0) {
            $this->line('Skipped '.$result['skipped'].' (staff login, or no phone/email).');
        }
        if ($result['fail'] > 0) {
            $this->warn('Failed: '.$result['fail']);
            foreach (array_slice($result['errors'], 0, 20) as $error) {
                $this->line('  '.$error);
            }
        }

        return $result['fail'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

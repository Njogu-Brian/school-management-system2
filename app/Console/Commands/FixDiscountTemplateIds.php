<?php

namespace App\Console\Commands;

use App\Models\FeeConcession;
use App\Models\DiscountTemplate;
use Illuminate\Console\Command;

class FixDiscountTemplateIds extends Command
{
    protected $signature = 'discounts:fix-template-ids';
    protected $description = 'Fix FeeConcession records missing discount_template_id';

    public function handle()
    {
        $this->info('Finding FeeConcession records without discount_template_id...');
        
        $concessions = FeeConcession::whereNull('discount_template_id')
            ->whereNotNull('discount_type')
            ->get();
        
        $this->info("Found {$concessions->count()} records to fix.");
        
        if ($concessions->isEmpty()) {
            $this->info('No records to fix.');
            return 0;
        }
        
        $fixed = 0;
        foreach ($concessions as $concession) {
            // Try to find a matching template based on discount characteristics
            $template = DiscountTemplate::where('discount_type', $concession->discount_type)
                ->where('type', $concession->type)
                ->where('scope', $concession->scope)
                ->where('frequency', $concession->frequency)
                ->where('value', $concession->value)
                ->first();
            
            if ($template) {
                $concession->update(['discount_template_id' => $template->id]);
                $this->line("Fixed concession ID {$concession->id} - linked to template: {$template->name}");
                $fixed++;
            } else {
                $this->warn("Could not find matching template for concession ID {$concession->id}");
            }
        }
        
        $this->info("Fixed {$fixed} records.");
        return 0;
    }
}


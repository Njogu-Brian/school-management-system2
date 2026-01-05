<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdatePaymentPlanStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-plans:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update payment plan and installment statuses (overdue, broken, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating payment plan statuses...');

        $updated = 0;

        DB::transaction(function () use (&$updated) {
            // Get all active payment plans
            $plans = FeePaymentPlan::whereIn('status', ['active', 'compliant', 'overdue'])
                ->with('installments')
                ->get();

            foreach ($plans as $plan) {
                $updatedPlan = false;

                // Check if final clearance deadline has passed
                if ($plan->final_clearance_deadline && 
                    Carbon::parse($plan->final_clearance_deadline)->isPast() &&
                    $plan->status !== 'completed' &&
                    $plan->status !== 'broken') {
                    
                    // Check if plan is fully paid
                    $totalPaid = $plan->installments->sum('paid_amount');
                    if ($totalPaid < $plan->total_amount) {
                        // Mark as broken if deadline passed and not fully paid
                        $plan->update(['status' => 'broken']);
                        $updatedPlan = true;
                    }
                }

                // Update installment statuses
                foreach ($plan->installments as $installment) {
                    $updatedInstallment = false;

                    // Mark overdue if due date passed and not fully paid
                    if ($installment->status !== 'paid' &&
                        Carbon::parse($installment->due_date)->isPast() &&
                        $installment->paid_amount < $installment->amount) {
                        
                        $installment->update(['status' => 'overdue']);
                        $updatedInstallment = true;
                    }

                    // Update partial status
                    if ($installment->status === 'pending' && $installment->paid_amount > 0) {
                        $installment->update(['status' => 'partial']);
                        $updatedInstallment = true;
                    }

                    if ($updatedInstallment) {
                        $updatedPlan = true;
                    }
                }

                // Refresh plan to check for overdue installments
                $plan->refresh();
                $hasOverdue = $plan->installments()->where('status', 'overdue')->exists();

                // Escalate plan to overdue if any installment is overdue
                if ($hasOverdue && !in_array($plan->status, ['overdue', 'broken', 'completed'])) {
                    $plan->update(['status' => 'overdue']);
                    $updatedPlan = true;
                }

                // Check if plan is completed
                $totalPaid = $plan->installments()->sum('paid_amount');
                if ($totalPaid >= $plan->total_amount && $plan->status !== 'completed') {
                    $plan->update(['status' => 'completed']);
                    $updatedPlan = true;
                }

                if ($updatedPlan) {
                    $updated++;
                }
            }
        });

        $this->info("Updated {$updated} payment plan(s).");
        return 0;
    }
}

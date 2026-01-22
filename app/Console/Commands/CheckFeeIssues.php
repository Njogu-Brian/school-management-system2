<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Student, Invoice, InvoiceItem, OptionalFee, Votehead};

class CheckFeeIssues extends Command
{
    protected $signature = 'check:fee-issues';
    protected $description = 'Check fee issues for Tyler RKS642 and Isaac RKS546';

    public function handle()
    {
        $this->info('Checking Tyler RKS642...');
        $tyler = Student::where('admission_number', 'RKS642')->first();
        if ($tyler) {
            $this->info("Tyler found: ID {$tyler->id}, Class: {$tyler->classroom_id}");
            
            // Get current year and term
            $year = (int) setting('current_year', date('Y'));
            $term = (int) setting('current_term', 1);
            
            $invoice = Invoice::where('student_id', $tyler->id)
                ->where('year', $year)
                ->where('term', $term)
                ->first();
            
            if ($invoice) {
                $this->info("Invoice found for {$year} Term {$term}");
                $items = $invoice->items()->with('votehead')->get();
                foreach ($items as $item) {
                    $voteheadName = $item->votehead ? $item->votehead->name : 'Unknown';
                    $this->line("  - {$voteheadName}: {$item->amount} (Source: {$item->source}, Status: {$item->status})");
                }
            } else {
                $this->warn("No invoice found for {$year} Term {$term}");
            }
        } else {
            $this->error('Tyler not found');
        }
        
        $this->newLine();
        $this->info('Checking Isaac RKS546...');
        $isaac = Student::where('admission_number', 'RKS546')->first();
        if ($isaac) {
            $this->info("Isaac found: ID {$isaac->id}");
            
            $year = (int) setting('current_year', date('Y'));
            $term = (int) setting('current_term', 1);
            
            // Check optional fees
            $optionalFees = OptionalFee::where('student_id', $isaac->id)
                ->where('year', $year)
                ->where('term', $term)
                ->with('votehead')
                ->get();
            
            $this->info("Optional fees for {$year} Term {$term}:");
            foreach ($optionalFees as $opt) {
                $voteheadName = $opt->votehead ? $opt->votehead->name : 'Unknown';
                $this->line("  - {$voteheadName}: {$opt->amount} (Status: {$opt->status})");
            }
            
            // Check invoice
            $invoice = Invoice::where('student_id', $isaac->id)
                ->where('year', $year)
                ->where('term', $term)
                ->first();
            
            if ($invoice) {
                $this->info("Invoice found for {$year} Term {$term}");
                $items = $invoice->items()->with('votehead')->get();
                foreach ($items as $item) {
                    $voteheadName = $item->votehead ? $item->votehead->name : 'Unknown';
                    $this->line("  - {$voteheadName}: {$item->amount} (Source: {$item->source}, Status: {$item->status})");
                }
            } else {
                $this->warn("No invoice found for {$year} Term {$term}");
            }
        } else {
            $this->error('Isaac not found');
        }
        
        return 0;
    }
}

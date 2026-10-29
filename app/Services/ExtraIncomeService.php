<?php

namespace App\Services;

use App\Models\ExtraIncomeItem;
use App\Models\InvoiceItem;
use App\Models\OptionalFee;
use App\Models\Student;
use App\Models\Votehead;
use Illuminate\Support\Facades\Auth;

class ExtraIncomeService
{
    /**
     * Bill every current student in the activity's class.
     */
    public function chargeClass(ExtraIncomeItem $item): int
    {
        if ($item->isSwimming()) {
            throw new \RuntimeException('Swimming is credited to the swimming wallet when you split a payment. It is not billed as a class invoice.');
        }

        $classroomIds = $item->classroomIds();
        if ($classroomIds === []) {
            throw new \RuntimeException('Choose at least one class before charging students.');
        }

        if (!$item->votehead_id) {
            throw new \RuntimeException('This activity has no votehead to bill against.');
        }

        $students = Student::query()
            ->whereIn('classroom_id', $classroomIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->get();

        foreach ($students as $student) {
            $this->ensureStudentCharge($item, $student);
        }

        return $students->count();
    }

    /**
     * Make sure this student has an invoice line for the activity, large enough
     * to absorb $minimumCharge (the amount about to be allocated).
     */
    public function ensureStudentCharge(ExtraIncomeItem $item, Student $student, ?float $minimumCharge = null): InvoiceItem
    {
        if ($item->isSwimming()) {
            throw new \RuntimeException('Swimming extra income is credited to the swimming wallet, not an invoice.');
        }

        if (!$item->votehead_id) {
            throw new \RuntimeException("{$item->name} has no votehead.");
        }

        $votehead = Votehead::find($item->votehead_id);
        if ($votehead && $this->voteheadCreditsSwimmingWallet($votehead)) {
            throw new \RuntimeException("{$item->name} uses a swimming votehead. Set the type to Swimming so the split credits the swimming wallet.");
        }

        if ($item->hasClassRestriction() && !$item->allowsClassroom((int) $student->classroom_id)) {
            $className = $item->classroomNames();
            throw new \RuntimeException("{$student->full_name} is not in {$className}, so this payment cannot go to {$item->name}.");
        }

        $target = round(max((float) $item->amount, (float) ($minimumCharge ?? 0)), 2);
        if ($target <= 0) {
            throw new \RuntimeException("{$item->name} has no amount to charge.");
        }

        $optional = OptionalFee::query()
            ->where('student_id', $student->id)
            ->where('votehead_id', $item->votehead_id)
            ->where('year', $item->year)
            ->where('term', $item->term)
            ->first();

        $optionalAmount = round(max($target, (float) ($optional->amount ?? 0)), 2);

        if ($optional) {
            $optional->update([
                'academic_year_id' => $item->academic_year_id,
                'amount' => $optionalAmount,
                'status' => 'billed',
                'assigned_by' => Auth::id(),
                'assigned_at' => $optional->assigned_at ?? now(),
            ]);
        } else {
            OptionalFee::create([
                'student_id' => $student->id,
                'votehead_id' => $item->votehead_id,
                'term' => $item->term,
                'year' => $item->year,
                'academic_year_id' => $item->academic_year_id,
                'amount' => $optionalAmount,
                'status' => 'billed',
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]);
        }

        $invoice = InvoiceService::ensure($student->id, (int) $item->year, (int) $item->term);

        $line = InvoiceItem::query()
            ->where('invoice_id', $invoice->id)
            ->where('votehead_id', $item->votehead_id)
            ->first();

        if ($line) {
            $needed = round(max((float) $line->amount, $optionalAmount, (float) $line->getAllocatedAmount()), 2);
            $line->update([
                'amount' => $needed,
                'original_amount' => $needed,
                'discount_amount' => $line->discount_amount ?? 0,
                'status' => 'active',
                'source' => 'extra_income',
                'custom_votehead_name' => $item->name,
                'posted_at' => $line->posted_at ?? now(),
            ]);
        } else {
            $line = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $item->votehead_id,
                'custom_votehead_name' => $item->name,
                'amount' => $optionalAmount,
                'discount_amount' => 0,
                'original_amount' => $optionalAmount,
                'status' => 'active',
                'source' => 'extra_income',
                'posted_at' => now(),
            ]);
        }

        InvoiceService::recalc($invoice);

        $line = $line->fresh();
        $shortfall = round((float) ($minimumCharge ?? 0) - $line->getBalance(), 2);
        if ($minimumCharge !== null && $shortfall > 0.009) {
            $raised = round((float) $line->amount + $shortfall, 2);
            $line->update([
                'amount' => $raised,
                'original_amount' => $raised,
            ]);
            OptionalFee::query()
                ->where('student_id', $student->id)
                ->where('votehead_id', $item->votehead_id)
                ->where('year', $item->year)
                ->where('term', $item->term)
                ->update(['amount' => $raised]);
            InvoiceService::recalc($invoice->fresh());
            $line = $line->fresh();
        }

        return $line;
    }

    public function ensureVotehead(ExtraIncomeItem $item): Votehead
    {
        if ($item->votehead_id && $item->votehead) {
            return $item->votehead;
        }

        $votehead = Votehead::create([
            'name' => $item->name,
            'description' => $item->description ?: $item->kindLabel() . ' extra income',
            'is_mandatory' => false,
            'is_optional' => true,
            'is_active' => true,
            'is_activity_fee' => true,
            'charge_type' => 'per_student',
        ]);

        $item->update(['votehead_id' => $votehead->id]);

        return $votehead;
    }

    protected function voteheadCreditsSwimmingWallet(Votehead $votehead): bool
    {
        $looksLikeSwimming = stripos($votehead->name ?? '', 'swimming') !== false
            || stripos($votehead->code ?? '', 'SWIM') !== false;

        return $looksLikeSwimming && !$votehead->is_mandatory;
    }
}

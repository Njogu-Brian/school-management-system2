<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use App\Models\RequirementTemplate;
use App\Models\StudentRequirement;
use Illuminate\Support\Facades\Auth;

class RequirementCustodyService
{
    /**
     * Map the "collect vs verify" checkboxes onto custody flags.
     * Verification-only always wins: the learner keeps the item.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function applyFlags(array $validated, bool $verify, bool $collect): array
    {
        if ($verify) {
            $validated['is_verification_only'] = true;
            $validated['leave_with_teacher'] = false;
            $validated['custody_type'] = 'parent_custody';
        } else {
            $validated['is_verification_only'] = false;
            $validated['leave_with_teacher'] = $collect;
            $validated['custody_type'] = $collect ? 'school_custody' : 'parent_custody';
        }

        return $validated;
    }

    /**
     * Persist template flags. If a collect item is later marked verify,
     * reverse only the stock that came from learner receipts (not purchases).
     *
     * @param  array<string, mixed>  $validated
     */
    public function syncTemplate(RequirementTemplate $template, array $validated, bool $verify, bool $collect): RequirementTemplate
    {
        $wasSchool = $template->exists && $template->addsToSchoolInventory();
        $validated = $this->applyFlags($validated, $verify, $collect);
        $template->fill($validated);
        $template->save();

        if ($wasSchool && ! $template->addsToSchoolInventory()) {
            $this->reverseSchoolStockForTemplate($template);
        }

        return $template;
    }

    /**
     * Take previously collected school-custody quantities back out of inventory.
     * Catalog items stay; only learner-receipt stock is removed.
     */
    public function reverseSchoolStockForTemplate(RequirementTemplate $template): int
    {
        $requirementIds = StudentRequirement::query()
            ->where('requirement_template_id', $template->id)
            ->pluck('id');

        if ($requirementIds->isEmpty()) {
            return 0;
        }

        $inTransactions = InventoryTransaction::query()
            ->whereIn('student_requirement_id', $requirementIds)
            ->where('type', 'in')
            ->get();

        $reversed = 0;
        foreach ($inTransactions as $tx) {
            $already = InventoryTransaction::query()
                ->where('student_requirement_id', $tx->student_requirement_id)
                ->where('type', 'out')
                ->where('reference_number', 'VERIFY-REVERSE-'.$tx->id)
                ->exists();

            if ($already) {
                continue;
            }

            InventoryTransaction::create([
                'inventory_item_id' => $tx->inventory_item_id,
                'user_id' => Auth::id(),
                'student_requirement_id' => $tx->student_requirement_id,
                'type' => 'out',
                'quantity' => $tx->quantity,
                'notes' => 'Removed from school inventory because this requirement is verification-only (learner keeps it as personal property).',
                'reference_number' => 'VERIFY-REVERSE-'.$tx->id,
            ]);
            $reversed++;
        }

        return $reversed;
    }
}

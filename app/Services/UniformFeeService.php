<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Votehead;
use Illuminate\Support\Facades\DB;

class UniformFeeService
{
    public const SOURCE = 'uniform';
    public const SOURCE_CUSTOM = 'custom_manual';

    /**
     * Source values that support direct amount/name edits without notes.
     */
    public static function managedSources(): array
    {
        return [self::SOURCE, self::SOURCE_CUSTOM];
    }

    public static function isManagedCustomItem(InvoiceItem $item): bool
    {
        return in_array($item->source, self::managedSources(), true);
    }

    /**
     * Get or create the Uniform votehead
     */
    public static function uniformVotehead(): Votehead
    {
        return Votehead::firstOrCreate(
            ['code' => 'UNIFORM'],
            [
                'name' => 'Uniform',
                'is_active' => true,
                'is_mandatory' => false,
            ]
        );
    }

    /**
     * Get the uniform invoice item for an invoice (if any)
     */
    public static function getUniformItem(Invoice $invoice): ?InvoiceItem
    {
        $votehead = self::uniformVotehead();
        return InvoiceItem::where('invoice_id', $invoice->id)
            ->where('votehead_id', $votehead->id)
            ->first(); // one votehead per invoice; source may be 'uniform' or legacy
    }

    /**
     * Check if invoice has a line that we manage as "uniform" (source=uniform)
     */
    public static function hasUniformLine(Invoice $invoice): bool
    {
        $item = self::getUniformItem($invoice);
        return $item && $item->source === self::SOURCE;
    }

    /**
     * Add or update uniform line on a student's invoice.
     * Updates fee balance and will appear in invoice, payments and statement.
     */
    public static function addOrUpdateUniform(Invoice $invoice, float $amount): InvoiceItem
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Uniform amount must be zero or positive.');
        }

        $votehead = self::uniformVotehead();

        return DB::transaction(function () use ($invoice, $amount, $votehead) {
            $item = self::getUniformItem($invoice);

            if ($item) {
                $item->update([
                    'amount' => $amount,
                    'original_amount' => $item->original_amount ?? $amount,
                    'status' => 'active',
                    'source' => self::SOURCE,
                    'discount_amount' => 0,
                ]);
            } else {
                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'votehead_id' => $votehead->id,
                    'amount' => $amount,
                    'original_amount' => $amount,
                    'discount_amount' => 0,
                    'status' => 'active',
                    'source' => self::SOURCE,
                ]);
            }

            app()->instance('auto_allocating', true);
            InvoiceService::recalc($invoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($invoice->student_id);
            app()->instance('auto_allocating', false);

            return $item->fresh();
        });
    }

    /**
     * Find existing votehead by name (case-insensitive) or create it.
     */
    public static function findOrCreateVotehead(string $voteheadName): Votehead
    {
        $name = trim($voteheadName);
        if ($name === '') {
            throw new \InvalidArgumentException('Votehead name is required.');
        }

        $existing = Votehead::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return $existing;
        }

        return Votehead::create([
            'name' => $name,
            'is_active' => true,
            'is_mandatory' => false,
            'is_optional' => true,
            'charge_type' => 'per_student',
        ]);
    }

    /**
     * Add or update a custom line item on an invoice.
     */
    public static function addCustomItem(Invoice $invoice, string $voteheadName, float $amount): InvoiceItem
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be zero or positive.');
        }

        return DB::transaction(function () use ($invoice, $voteheadName, $amount) {
            $votehead = self::findOrCreateVotehead($voteheadName);

            $item = InvoiceItem::where('invoice_id', $invoice->id)
                ->where('votehead_id', $votehead->id)
                ->first();

            if ($item) {
                $voteheadCode = strtoupper((string) optional($item->votehead)->code);
                $isLegacyUniformLine = $voteheadCode === 'UNIFORM';
                if (!self::isManagedCustomItem($item) && !$isLegacyUniformLine) {
                    throw new \RuntimeException('This votehead already exists on the invoice as a posted fee item. Edit that line directly or choose another custom votehead name.');
                }

                $item->update([
                    'amount' => $amount,
                    'original_amount' => $item->original_amount ?? $amount,
                    'status' => 'active',
                    'source' => self::SOURCE_CUSTOM,
                    'discount_amount' => 0,
                ]);
            } else {
                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'votehead_id' => $votehead->id,
                    'amount' => $amount,
                    'original_amount' => $amount,
                    'discount_amount' => 0,
                    'status' => 'active',
                    'source' => self::SOURCE_CUSTOM,
                ]);
            }

            app()->instance('auto_allocating', true);
            InvoiceService::recalc($invoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($invoice->student_id);
            app()->instance('auto_allocating', false);

            return $item->fresh(['votehead']);
        });
    }

    /**
     * Update managed custom/uniform item directly (amount + votehead name).
     */
    public static function updateManagedItem(InvoiceItem $item, string $voteheadName, float $newAmount): InvoiceItem
    {
        if (!self::isManagedCustomItem($item)) {
            throw new \InvalidArgumentException('Item is not managed as a custom invoice line.');
        }
        if ($newAmount < 0) {
            throw new \InvalidArgumentException('Amount must be zero or positive.');
        }

        return DB::transaction(function () use ($item, $voteheadName, $newAmount) {
            $votehead = self::findOrCreateVotehead($voteheadName);

            $duplicateItem = InvoiceItem::where('invoice_id', $item->invoice_id)
                ->where('votehead_id', $votehead->id)
                ->where('id', '!=', $item->id)
                ->first();
            if ($duplicateItem) {
                throw new \RuntimeException('This invoice already has an item with that votehead name.');
            }

            $item->update([
                'votehead_id' => $votehead->id,
                'amount' => $newAmount,
                'original_amount' => $item->original_amount ?? $newAmount,
                'source' => self::SOURCE_CUSTOM,
            ]);

            app()->instance('auto_allocating', true);
            InvoiceService::recalc($item->invoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($item->invoice->student_id);
            app()->instance('auto_allocating', false);

            // If a Term 1 (2026+) invoice is edited, keep Term 2/3 carry-forward in sync.
            if ((int) ($item->invoice->year ?? 0) >= 2026 && (int) ($item->invoice->term ?? 0) === 1) {
                InvoiceService::syncCarryForwardFromTerm1($item->invoice);
            }

            return $item->fresh(['votehead']);
        });
    }

    /**
     * Remove a managed custom/uniform line from invoice.
     */
    public static function removeManagedItem(InvoiceItem $item): void
    {
        if (!self::isManagedCustomItem($item)) {
            return;
        }

        DB::transaction(function () use ($item) {
            $invoice = $item->invoice;
            $item->delete();

            app()->instance('auto_allocating', true);
            InvoiceService::recalc($invoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($invoice->student_id);
            app()->instance('auto_allocating', false);

            if ((int) ($invoice->year ?? 0) >= 2026 && (int) ($invoice->term ?? 0) === 1) {
                InvoiceService::syncCarryForwardFromTerm1($invoice);
            }
        });
    }

    /**
     * Update uniform amount directly (no credit/debit notes).
     */
    public static function updateUniformAmount(InvoiceItem $item, float $newAmount): InvoiceItem
    {
        if ($item->source !== self::SOURCE) {
            throw new \InvalidArgumentException('Item is not a uniform line.');
        }
        if ($newAmount < 0) {
            throw new \InvalidArgumentException('Uniform amount must be zero or positive.');
        }

        return DB::transaction(function () use ($item, $newAmount) {
            $item->update([
                'amount' => $newAmount,
                'original_amount' => $item->original_amount ?? $newAmount,
            ]);

            app()->instance('auto_allocating', true);
            InvoiceService::recalc($item->invoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($item->invoice->student_id);
            app()->instance('auto_allocating', false);

            if ((int) ($item->invoice->year ?? 0) >= 2026 && (int) ($item->invoice->term ?? 0) === 1) {
                InvoiceService::syncCarryForwardFromTerm1($item->invoice);
            }

            return $item->fresh();
        });
    }

    /**
     * Remove legacy uniform line from invoice (soft-delete the item).
     */
    public static function removeLegacyUniformItem(Invoice $invoice): void
    {
        $item = self::getUniformItem($invoice);
        if (!$item || $item->source !== self::SOURCE) {
            return;
        }

        DB::transaction(function () use ($item, $invoice) {
            $item->delete(); // soft delete

            app()->instance('auto_allocating', true);
            InvoiceService::recalc($invoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($invoice->student_id);
            app()->instance('auto_allocating', false);

            if ((int) ($invoice->year ?? 0) >= 2026 && (int) ($invoice->term ?? 0) === 1) {
                InvoiceService::syncCarryForwardFromTerm1($invoice);
            }
        });
    }

    /**
     * @deprecated Use removeLegacyUniformItem().
     */
    public static function removeUniform(Invoice $invoice): void
    {
        self::removeLegacyUniformItem($invoice);
    }
}

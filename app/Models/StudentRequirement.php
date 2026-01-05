<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRequirement extends Model
{
    protected $fillable = [
        'student_id', 'requirement_template_id', 'academic_year_id', 'term_id',
        'collected_by', 'quantity_required', 'expected_quantity', 'quantity_collected', 
        'quantity_missing', 'balance_pending', 'status', 'collected_at', 'last_received_at',
        'can_update_receipt', 'notes', 'notified_parent',
        'pos_order_id', 'pos_order_item_id', 'purchased_through_pos'
    ];

    protected $casts = [
        'quantity_required' => 'decimal:2',
        'expected_quantity' => 'decimal:2',
        'quantity_collected' => 'decimal:2',
        'quantity_missing' => 'decimal:2',
        'balance_pending' => 'decimal:2',
        'collected_at' => 'datetime',
        'last_received_at' => 'datetime',
        'can_update_receipt' => 'boolean',
        'notified_parent' => 'boolean',
        'purchased_through_pos' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requirementTemplate()
    {
        return $this->belongsTo(RequirementTemplate::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }

    public function inventoryTransaction()
    {
        return $this->hasOne(InventoryTransaction::class);
    }

    public function posOrder()
    {
        return $this->belongsTo(\App\Models\Pos\Order::class, 'pos_order_id');
    }

    public function posOrderItem()
    {
        return $this->belongsTo(\App\Models\Pos\OrderItem::class, 'pos_order_item_id');
    }

    public function receipts()
    {
        return $this->hasMany(ItemReceipt::class);
    }

    public function updateStatus()
    {
        $expected = $this->expected_quantity ?? $this->quantity_required;
        
        if ($this->quantity_collected >= $expected) {
            $this->status = 'complete';
            $this->quantity_missing = 0;
            $this->balance_pending = 0;
        } elseif ($this->quantity_collected > 0) {
            $this->status = 'partial';
            $this->quantity_missing = $expected - $this->quantity_collected;
            $this->balance_pending = $this->quantity_missing;
        } else {
            $this->status = 'pending';
            $this->quantity_missing = $expected;
            $this->balance_pending = $expected;
        }
        $this->save();
    }

    /**
     * Record a receipt of items
     */
    public function recordReceipt($quantity, $receivedBy, $receiptStatus = 'fully_received', $notes = null)
    {
        $receipt = ItemReceipt::create([
            'student_requirement_id' => $this->id,
            'student_id' => $this->student_id,
            'classroom_id' => $this->student->classroom_id,
            'received_by' => $receivedBy,
            'quantity_received' => $quantity,
            'receipt_status' => $receiptStatus,
            'notes' => $notes,
            'received_at' => now(),
        ]);

        // Update collected quantity
        $this->quantity_collected += $quantity;
        $this->last_received_at = now();
        $this->updateStatus();

        // If school custody, add to inventory
        if ($this->requirementTemplate && $this->requirementTemplate->isSchoolCustody()) {
            $this->addToInventory($quantity, $receivedBy, $receipt);
        }

        return $receipt;
    }

    /**
     * Add received items to school inventory (for school custody items)
     */
    protected function addToInventory($quantity, $receivedBy, $receipt)
    {
        $template = $this->requirementTemplate;
        if (!$template) {
            return;
        }

        // Load requirement type if not already loaded
        if (!$template->relationLoaded('requirementType')) {
            $template->load('requirementType');
        }

        $requirementType = $template->requirementType;
        $itemName = $requirementType->name ?? 'Unknown Item';
        $category = $requirementType->category ?? 'student_stationery';

        // Find or create inventory item
        $inventoryItem = InventoryItem::firstOrCreate(
            [
                'name' => $itemName,
                'category' => $category,
            ],
            [
                'quantity' => 0,
                'unit' => $template->unit ?? 'piece',
                'is_active' => true,
            ]
        );

        // Create inventory transaction
        $classroomName = $this->student->classroom->name ?? 'N/A';
        InventoryTransaction::create([
            'inventory_item_id' => $inventoryItem->id,
            'user_id' => $receivedBy,
            'student_requirement_id' => $this->id,
            'type' => 'in',
            'quantity' => $quantity,
            'notes' => "Received from student: {$this->student->first_name} {$this->student->last_name} (Class: {$classroomName})",
            'reference_number' => 'RECEIPT-' . $receipt->id,
        ]);
    }

    /**
     * Check if payment threshold is met or payment plan exists
     */
    public function canReceiveItems(): array
    {
        $student = $this->student;
        $term = $this->term;
        
        if (!$term) {
            return ['allowed' => true, 'reason' => 'No term specified'];
        }

        $complianceService = app(\App\Services\PaymentPlanComplianceService::class);
        $classification = $complianceService->classifyStudentCompliance($student, $term);
        
        // Check if payment plan exists
        $hasPaymentPlan = \App\Models\FeePaymentPlan::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->whereIn('status', ['active', 'pending', 'partial'])
            ->exists();

        if ($classification === 'above_threshold' || $classification === 'no_threshold') {
            return ['allowed' => true, 'reason' => 'Payment threshold met or no threshold'];
        }

        if ($hasPaymentPlan) {
            return ['allowed' => true, 'reason' => 'Payment plan exists'];
        }

        return [
            'allowed' => false,
            'reason' => 'Payment threshold not met and no payment plan exists. Please visit accounts office.',
            'classification' => $classification
        ];
    }
}

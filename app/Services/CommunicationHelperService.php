<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Staff;
use App\Models\Invoice;
use App\Models\SwimmingWallet;
use Illuminate\Support\Facades\DB;

class CommunicationHelperService
{
    /**
     * Build a map of recipients => entity used for personalization.
     * $target: students|parents|staff|class|student|one_parent|specific_students|custom|all
     * $data: ['target', 'classroom_id', 'classroom_ids', 'student_id', 'selected_student_ids', 'custom_emails', 'custom_numbers',
     *         'fee_balance_only', 'swimming_balance_only', 'upcoming_invoices_only', 'fee_balance_min', 'fee_balance_percent_min',
     *         'swimming_balance_min', 'prior_term_balance_only', 'prior_term_balance_min', 'exclude_student_ids', 'exclude_staff']
     * $type: 'email', 'sms', or 'whatsapp'
     */
    public static function collectRecipients(array $data, string $type): array
    {
        $out = [];
        $target = $data['target'];
        $custom = $data['custom_emails'] ?? $data['custom_numbers'] ?? null;

        // Custom manual entries
        if ($custom) {
            foreach (array_map('trim', explode(',', $custom)) as $item) {
                if ($item !== '') $out[$item] = null;
            }
        }

        // Specific multiple students (exclude alumni and archived)
        if ($target === 'specific_students' && !empty($data['selected_student_ids'])) {
            $studentIds = is_array($data['selected_student_ids']) 
                ? $data['selected_student_ids'] 
                : array_filter(explode(',', $data['selected_student_ids']));
            
            if (!empty($studentIds)) {
                Student::with('parent', 'classroom')
                    ->whereIn('id', $studentIds)
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->get()
                    ->each(function ($s) use (&$out, $type) {
                        if ($s->parent) {
                            // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                            $contacts = match ($type) {
                                'email' => [$s->parent->father_email, $s->parent->mother_email],
                                'whatsapp' => [
                                    !empty($s->parent->father_whatsapp) ? $s->parent->father_whatsapp : $s->parent->father_phone,
                                    !empty($s->parent->mother_whatsapp) ? $s->parent->mother_whatsapp : $s->parent->mother_phone,
                                ],
                                default => [$s->parent->father_phone, $s->parent->mother_phone],
                            };
                            foreach ($contacts as $c) {
                                if ($c) {
                                    if (!isset($out[$c])) $out[$c] = [];
                                    if (!in_array($s, $out[$c], true)) $out[$c][] = $s;
                                }
                            }
                        }
                    });
            }
        }

        // Single student / one parent (exclude alumni and archived)
        if (($target === 'student' || $target === 'one_parent') && !empty($data['student_id'])) {
            $student = Student::with('parent', 'classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->find($data['student_id']);
            if ($student && $student->parent) {
                // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                $contacts = match ($type) {
                    'email' => [$student->parent->father_email, $student->parent->mother_email],
                    'whatsapp' => [
                        !empty($student->parent->father_whatsapp) ? $student->parent->father_whatsapp : $student->parent->father_phone,
                        !empty($student->parent->mother_whatsapp) ? $student->parent->mother_whatsapp : $student->parent->mother_phone,
                    ],
                    default => [$student->parent->father_phone, $student->parent->mother_phone],
                };
                foreach ($contacts as $c) {
                    if ($c) $out[$c] = [$student];
                }
            }
        }

        // Entire class(es) (exclude alumni and archived) – supports single or multiple classrooms
        $classroomIds = self::normalizeClassroomIds($data);
        if ($target === 'class' && !empty($classroomIds)) {
            Student::with('parent', 'classroom', 'stream')
                ->whereIn('classroom_id', $classroomIds)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->activeForCurrentTerm()
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    if ($s->parent) {
                        // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                        $contacts = match ($type) {
                            'email' => [$s->parent->father_email, $s->parent->mother_email],
                            'whatsapp' => [
                                !empty($s->parent->father_whatsapp) ? $s->parent->father_whatsapp : $s->parent->father_phone,
                                !empty($s->parent->mother_whatsapp) ? $s->parent->mother_whatsapp : $s->parent->mother_phone,
                            ],
                            default => [$s->parent->father_phone, $s->parent->mother_phone],
                        };
                        foreach ($contacts as $c) {
                            if ($c) {
                                if (!isset($out[$c])) $out[$c] = [];
                                if (!in_array($s, $out[$c], true)) $out[$c][] = $s;
                            }
                        }
                    }
                });
        }

        // All students (via parent contacts) – every non-archived, non-alumni student active for current term; send to parent when available
        // 'all' is alias for 'parents' for fee communications
        if ($target === 'parents' || $target === 'all') {
            Student::with('parent', 'classroom', 'stream')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->activeForCurrentTerm()
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    if ($s->parent) {
                        // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                        $contacts = match ($type) {
                            'email' => [$s->parent->father_email, $s->parent->mother_email],
                            'whatsapp' => [
                                !empty($s->parent->father_whatsapp) ? $s->parent->father_whatsapp : $s->parent->father_phone,
                                !empty($s->parent->mother_whatsapp) ? $s->parent->mother_whatsapp : $s->parent->mother_phone,
                            ],
                            default => [$s->parent->father_phone, $s->parent->mother_phone],
                        };
                        foreach ($contacts as $c) {
                            if ($c) {
                                if (!isset($out[$c])) $out[$c] = [];
                                if (!in_array($s, $out[$c], true)) $out[$c][] = $s;
                            }
                        }
                    }
                });
        }

        // All students (via student contact – email/phone)
        if ($target === 'students') {
            Student::where('archive', 0)
                ->where('is_alumni', false)
                ->activeForCurrentTerm()
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    $contact = match ($type) {
                        'email' => $s->email,
                        'whatsapp' => $s->phone_number,
                        default => $s->phone_number,
                    };
                    if ($contact) $out[$contact] = $s;
                });
        }

        // All staff
        if ($target === 'staff') {
            Staff::all()->each(function ($st) use (&$out, $type) {
                $contact = match ($type) {
                    'email' => $st->email,
                    'whatsapp' => $st->phone_number,
                    default => $st->phone_number,
                };
                if ($contact) $out[$contact] = $st;
            });
        }

        // Exclude specific students (applies to any target that yields student/parent recipients)
        $excludeIds = [];
        if (!empty($data['exclude_student_ids'])) {
            $excludeIds = is_array($data['exclude_student_ids'])
                ? array_map('intval', $data['exclude_student_ids'])
                : array_filter(array_map('intval', explode(',', (string) $data['exclude_student_ids'])));
        }
        if (!empty($excludeIds)) {
            $out = array_map(function ($entities) use ($excludeIds) {
                $list = is_array($entities) ? $entities : [$entities];
                $filtered = array_filter($list, fn ($e) => !($e instanceof Student) || !in_array((int) $e->id, $excludeIds, true));
                return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
            }, $out);
            $out = array_filter($out);
        }

        // Only recipients with fee balance (students/parents who have at least one invoice with balance > 0)
        if (!empty($data['fee_balance_only'])) {
            $today = now()->toDateString();
            $studentIdsWithBalance = Invoice::where('balance', '>', 0)
                ->where('status', '!=', 'reversed')
                ->where(function ($q) use ($today) {
                    // Communicate only due/current-or-past term balances.
                    $q->whereDate('due_date', '<=', $today)
                        ->orWhere(function ($sub) use ($today) {
                            $sub->whereNull('due_date')
                                ->where(function ($termFilter) use ($today) {
                                    $termFilter->whereNull('term_id')
                                        ->orWhereHas('term', function ($termQuery) use ($today) {
                                            $termQuery->whereNull('opening_date')
                                                ->orWhereDate('opening_date', '<=', $today);
                                        });
                                });
                        });
                })
                ->distinct()
                ->pluck('student_id')
                ->flip()
                ->all();
            $out = array_map(function ($entities) use ($studentIdsWithBalance) {
                $list = is_array($entities) ? $entities : [$entities];
                $filtered = array_filter($list, fn ($e) => !($e instanceof Student) || isset($studentIdsWithBalance[$e->id]));
                return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
            }, $out);
            $out = array_filter($out);
        }

        // Only recipients with upcoming invoices (invoices not yet due: due_date > today, balance > 0)
        if (!empty($data['upcoming_invoices_only'])) {
            $today = now()->toDateString();
            $studentIdsWithUpcoming = Invoice::where('balance', '>', 0)
                ->where('status', '!=', 'reversed')
                ->whereDate('due_date', '>', $today)
                ->distinct()
                ->pluck('student_id')
                ->flip()
                ->all();
            $out = array_map(function ($entities) use ($studentIdsWithUpcoming) {
                $list = is_array($entities) ? $entities : [$entities];
                $filtered = array_filter($list, fn ($e) => !($e instanceof Student) || isset($studentIdsWithUpcoming[$e->id]));
                return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
            }, $out);
            $out = array_filter($out);
        }

        // Only recipients with swimming balance (SwimmingWallet.balance < 0)
        if (!empty($data['swimming_balance_only'])) {
            $studentIdsWithSwimmingBalance = SwimmingWallet::where('balance', '<', 0)
                ->pluck('student_id')
                ->flip()
                ->all();
            $out = array_map(function ($entities) use ($studentIdsWithSwimmingBalance) {
                $list = is_array($entities) ? $entities : [$entities];
                $filtered = array_filter($list, fn ($e) => !($e instanceof Student) || isset($studentIdsWithSwimmingBalance[$e->id]));
                return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
            }, $out);
            $out = array_filter($out);
        }

        // Fee balance amount threshold (outstanding >= X)
        if (isset($data['fee_balance_min']) && $data['fee_balance_min'] !== '' && $data['fee_balance_min'] !== null) {
            $min = (float) $data['fee_balance_min'];
            if ($min > 0) {
                $out = array_map(function ($entities) use ($min) {
                    $list = is_array($entities) ? $entities : [$entities];
                    $filtered = array_filter($list, function ($e) use ($min) {
                        if (!($e instanceof Student)) return true;
                        $balance = StudentBalanceService::getTotalOutstandingBalance($e, false);
                        return $balance >= $min;
                    });
                    return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
                }, $out);
                $out = array_filter($out);
            }
        }

        // Fee balance percentage threshold (% of current term fees unpaid >= X)
        if (isset($data['fee_balance_percent_min']) && $data['fee_balance_percent_min'] !== '' && $data['fee_balance_percent_min'] !== null) {
            $percentMin = (float) $data['fee_balance_percent_min'];
            if ($percentMin > 0) {
                $currentTermId = get_current_term_id();
                $out = array_map(function ($entities) use ($percentMin, $currentTermId) {
                    $list = is_array($entities) ? $entities : [$entities];
                    $filtered = array_filter($list, function ($e) use ($percentMin, $currentTermId) {
                        if (!($e instanceof Student)) return true;
                        $outstanding = StudentBalanceService::getTotalOutstandingBalance($e, false);
                        if ($outstanding <= 0) return false;
                        $termTotal = Invoice::where('student_id', $e->id)
                            ->where('status', '!=', 'reversed')
                            ->when($currentTermId, fn ($q) => $q->where('term_id', $currentTermId))
                            ->sum('total');
                        if ($termTotal <= 0) return false;
                        $percent = ($outstanding / $termTotal) * 100;
                        return $percent >= $percentMin;
                    });
                    return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
                }, $out);
                $out = array_filter($out);
            }
        }

        // Swimming balance amount threshold (abs(balance) >= X when balance < 0)
        if (isset($data['swimming_balance_min']) && $data['swimming_balance_min'] !== '' && $data['swimming_balance_min'] !== null) {
            $min = (float) $data['swimming_balance_min'];
            if ($min > 0) {
                $out = array_map(function ($entities) use ($min) {
                    $list = is_array($entities) ? $entities : [$entities];
                    $filtered = array_filter($list, function ($e) use ($min) {
                        if (!($e instanceof Student)) return true;
                        $wallet = SwimmingWallet::getOrCreateForStudent($e->id);
                        return $wallet->balance < 0 && abs((float) $wallet->balance) >= $min;
                    });
                    return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
                }, $out);
                $out = array_filter($out);
            }
        }

        // Only recipients with prior-term carry-forward balance (Balance from prior term(s) invoice items)
        if (!empty($data['prior_term_balance_only'])) {
            $currentYear = (int) (setting('current_year') ?? date('Y'));
            $currentTerm = (int) (get_current_term_number() ?? 0);

            // Get student IDs that have an unpaid prior-term invoice balance (term < current term, same year)
            $priorInvoiceStudentIds = [];
            if ($currentTerm > 1) {
                $priorInvoiceStudentIds = \App\Models\Invoice::where('status', '!=', 'reversed')
                    ->where('year', $currentYear)
                    ->where('term', '<', $currentTerm)
                    ->where('balance', '>', 0)
                    ->distinct()
                    ->pluck('student_id')
                    ->flip()
                    ->all();
            }

            // Also include legacy carry-forward lines (for installations that use them)
            $carryForwardStudentIds = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('payment_allocations', 'payment_allocations.invoice_item_id', '=', 'invoice_items.id')
                ->whereNull('invoice_items.deleted_at')
                ->where('invoice_items.status', 'active')
                ->where('invoice_items.source', 'prior_term_carryforward')
                ->where('invoices.status', '!=', 'reversed')
                ->groupBy('invoices.student_id', 'invoice_items.id', 'invoice_items.amount', 'invoice_items.discount_amount')
                ->havingRaw('(COALESCE(invoice_items.amount,0) - COALESCE(invoice_items.discount_amount,0) - COALESCE(SUM(payment_allocations.amount),0)) > 0.01')
                ->pluck('invoices.student_id')
                ->flip()
                ->all();

            $unpaidStudentIds = $priorInvoiceStudentIds + $carryForwardStudentIds;

            $out = array_map(function ($entities) use ($unpaidStudentIds) {
                $list = is_array($entities) ? $entities : [$entities];
                $filtered = array_filter($list, fn ($e) => !($e instanceof Student) || isset($unpaidStudentIds[$e->id]));
                return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
            }, $out);
            $out = array_filter($out);
        }

        // Prior-term balance amount threshold (outstanding >= X)
        if (isset($data['prior_term_balance_min']) && $data['prior_term_balance_min'] !== '' && $data['prior_term_balance_min'] !== null) {
            $min = (float) $data['prior_term_balance_min'];
            if ($min > 0) {
                $out = array_map(function ($entities) use ($min) {
                    $list = is_array($entities) ? $entities : [$entities];
                    $filtered = array_filter($list, function ($e) use ($min) {
                        if (!($e instanceof Student)) return true;
                        $balance = StudentBalanceService::getOutstandingPriorTermArrears($e);
                        return $balance >= $min;
                    });
                    return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? array_values($filtered) : null);
                }, $out);
                $out = array_filter($out);
            }
        }

        // Exclude students in staff category (children whose student category name is "Staff")
        if (!empty($data['exclude_staff'])) {
            $out = array_map(function ($entities) {
                $list = is_array($entities) ? $entities : [$entities];
                $filtered = [];
                foreach ($list as $e) {
                    if (!($e instanceof Student)) {
                        $filtered[] = $e;
                    } else {
                        $e->loadMissing('category');
                        if (!$e->category || strtolower($e->category->name) !== 'staff') {
                            $filtered[] = $e;
                        }
                    }
                }
                return count($filtered) === 1 ? reset($filtered) : (count($filtered) > 1 ? $filtered : null);
            }, $out);
            $out = array_filter($out);
        }

        return $out;
    }

    /**
     * Normalize classroom_id(s) to array of IDs. Supports classroom_ids (array or comma-separated) and classroom_id (single).
     */
    public static function normalizeClassroomIds(array $data): array
    {
        if (!empty($data['classroom_ids'])) {
            $ids = $data['classroom_ids'];
            if (is_array($ids)) {
                return array_values(array_filter(array_map('intval', $ids)));
            }
            return array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
        }
        if (!empty($data['classroom_id'])) {
            return [(int) $data['classroom_id']];
        }
        return [];
    }

    /**
     * Expand recipients (contact => entity or contact => entity[]) into flat list of [contact, entity] pairs.
     * Use when sending to ensure siblings sharing the same contact each get a separate personalized message.
     */
    public static function expandRecipientsToPairs(array $rawRecipients): array
    {
        $pairs = [];
        foreach ($rawRecipients as $contact => $entityOrEntities) {
            $entities = is_array($entityOrEntities) ? $entityOrEntities : [$entityOrEntities];
            foreach ($entities as $entity) {
                $pairs[] = [$contact, $entity];
            }
        }
        return $pairs;
    }
}

<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use App\Models\CommunicationPlaceholder;

/**
 * settings (key-value) - Unified settings helper
 * This replaces both setting() and system_setting() functions
 */
if (!function_exists('setting')) {
    function setting($key, $default = null) {
        return Setting::get($key, $default);
    }
}

if (!function_exists('setting_set')) {
    function setting_set($key, $value) {
        return Setting::set($key, $value);
    }
}

/**
 * Increment a numeric setting value
 */
if (!function_exists('setting_increment')) {
    function setting_increment($key, $by = 1, $defaultStart = 0) {
        return Setting::incrementValue($key, $by, $defaultStart);
    }
}

/**
 * Get boolean setting value
 */
if (!function_exists('setting_bool')) {
    function setting_bool($key, $default = false) {
        return Setting::getBool($key, $default);
    }
}

/**
 * Set boolean setting value
 */
if (!function_exists('setting_set_bool')) {
    function setting_set_bool($key, $value) {
        return Setting::setBool($key, $value);
    }
}

/**
 * Get integer setting value
 */
if (!function_exists('setting_int')) {
    function setting_int($key, $default = 0) {
        return Setting::getInt($key, $default);
    }
}

/**
 * Set integer setting value
 */
if (!function_exists('setting_set_int')) {
    function setting_set_int($key, $value) {
        return Setting::setInt($key, $value);
    }
}

/**
 * Legacy system_setting() function - now uses settings table
 * @deprecated Use setting() instead. This is kept for backward compatibility.
 */
if (!function_exists('system_setting')) {
    function system_setting($key, $default = null) {
        return setting($key, $default);
    }
}

/**
 * Legacy system_setting_set() function - now uses settings table
 * @deprecated Use setting_set() instead. This is kept for backward compatibility.
 */
if (!function_exists('system_setting_set')) {
    function system_setting_set($key, $value) {
        return setting_set($key, $value);
    }
}

/**
 * Directory where branding images (logo, login background) are stored.
 * When PUBLIC_WEB_ROOT is set (split deployment), returns that path + /images.
 * Otherwise returns public_path('images').
 */
if (!function_exists('public_images_path')) {
    function public_images_path(string $subpath = ''): string {
        $base = config('app.public_web_root')
            ? rtrim(config('app.public_web_root'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'images'
            : public_path('images');
        return $subpath !== '' ? $base . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $subpath), DIRECTORY_SEPARATOR) : $base;
    }
}

/**
 * Full URL for a file in the public images folder (logo, login background).
 * Uses asset() so ASSET_URL is respected when public files are on another domain.
 */
if (!function_exists('public_image_url')) {
    function public_image_url(?string $filename): ?string {
        if ($filename === null || $filename === '') {
            return null;
        }
        return asset('images/' . ltrim($filename, '/'));
    }
}

/**
 * can_access:
 *  can_access('module.feature.action') OR can_access('module','feature','action')
 */
if (!function_exists('can_access')) {
    function can_access($module, $feature = null, $action = null): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Super Admin has all access
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        // Teachers and Senior Teachers have access to their routes (they're already protected by role middleware)
        if (method_exists($user, 'hasRole') && ($user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher'))) {
            // For teacher routes, allow access if they have the role
            // Permission checks can be done in controllers for granular control
            return true;
        }

        $permission = $feature === null
            ? (string)$module
            : "{$module}.{$feature}" . ($action ? ".{$action}" : '');

        return $user->can($permission);
    }
}

/**
 * Get or create a payment link for a student (no expiry, no click limit).
 * Siblings share one family link: one link per family, parent can pay one transaction for all.
 * Students without family_id get a single-student link.
 *
 * @param \App\Models\Student $student
 * @return \App\Models\PaymentLink|null
 */
if (!function_exists('get_or_create_payment_link_for_student')) {
    function get_or_create_payment_link_for_student($student)
    {
        if (!$student || !$student->id || !class_exists(\App\Models\PaymentLink::class)) {
            return null;
        }

        $UNLIMITED_USES = 999999;

        // Siblings share one link: prefer family link (student_id null, family_id set)
        if ($student->family_id) {
            $familyLink = \App\Models\PaymentLink::active()
                ->where('family_id', $student->family_id)
                ->whereNull('student_id')
                ->first();

            if ($familyLink) {
                return $familyLink;
            }

            // Create one family link for all siblings; amount = total family balance
            $familyTotalBalance = 0;
            if (class_exists(\App\Models\Invoice::class) && class_exists(\App\Models\Student::class)) {
                $siblingIds = \App\Models\Student::where('family_id', $student->family_id)->pluck('id');
                $familyTotalBalance = \App\Models\Invoice::whereIn('student_id', $siblingIds)
                    ->get()
                    ->sum(fn($inv) => max(0, (float) $inv->balance));
            }
            $familyTotalBalance = $familyTotalBalance > 0 ? round($familyTotalBalance, 2) : 0;

            $link = \App\Models\PaymentLink::create([
                'student_id' => null,
                'invoice_id' => null,
                'family_id' => $student->family_id,
                'amount' => $familyTotalBalance,
                'currency' => 'KES',
                'description' => 'Pay fee balance - All children',
                'account_reference' => 'FAM-' . $student->family_id,
                'status' => 'active',
                'expires_at' => null,
                'max_uses' => $UNLIMITED_USES,
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
                'metadata' => ['source' => 'sms_placeholder'],
            ]);

            return $link;
        }

        // No family: one link per student
        $existing = \App\Models\PaymentLink::active()
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalOutstanding = 0;
        $latestInvoice = null;
        if (class_exists(\App\Models\Invoice::class)) {
            $invoices = \App\Models\Invoice::where('student_id', $student->id)->get();
            $totalOutstanding = $invoices->sum(fn($inv) => max(0, (float) $inv->balance));
            $latestInvoice = \App\Models\Invoice::where('student_id', $student->id)
                ->orderBy('year', 'desc')
                ->orderBy('term', 'desc')
                ->first();
        }

        $amount = $totalOutstanding > 0 ? round($totalOutstanding, 2) : 0;
        $description = 'Pay fee balance - ' . ($student->full_name ?? $student->name ?? 'Student');
        $accountRef = $student->admission_number ?? ('STU-' . $student->id);

        $link = \App\Models\PaymentLink::create([
            'student_id' => $student->id,
            'invoice_id' => $latestInvoice ? $latestInvoice->id : null,
            'family_id' => null,
            'amount' => $amount,
            'currency' => 'KES',
            'description' => $description,
            'account_reference' => $accountRef,
            'status' => 'active',
            'expires_at' => null,
            'max_uses' => $UNLIMITED_USES,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
            'metadata' => ['source' => 'sms_placeholder'],
        ]);

        return $link;
    }
}

/**
 * When siblings are linked (family_id set or family merged), ensure one shared family payment link
 * and expire old per-student links for that family (like profile update link).
 *
 * @param int $familyId
 * @return \App\Models\PaymentLink|null
 */
if (!function_exists('ensure_family_payment_link')) {
    function ensure_family_payment_link($familyId)
    {
        if (!$familyId || !class_exists(\App\Models\PaymentLink::class) || !class_exists(\App\Models\Student::class)) {
            return null;
        }

        $UNLIMITED_USES = 999999;

        $familyLink = \App\Models\PaymentLink::active()
            ->where('family_id', $familyId)
            ->whereNull('student_id')
            ->first();

        if (!$familyLink) {
            $siblingIds = \App\Models\Student::where('family_id', $familyId)->pluck('id');
            $familyTotalBalance = 0;
            if (class_exists(\App\Models\Invoice::class) && $siblingIds->isNotEmpty()) {
                $familyTotalBalance = \App\Models\Invoice::whereIn('student_id', $siblingIds)
                    ->get()
                    ->sum(fn($inv) => max(0, (float) $inv->balance));
            }
            $familyTotalBalance = $familyTotalBalance > 0 ? round($familyTotalBalance, 2) : 0;

            $familyLink = \App\Models\PaymentLink::create([
                'student_id' => null,
                'invoice_id' => null,
                'family_id' => $familyId,
                'amount' => $familyTotalBalance,
                'currency' => 'KES',
                'description' => 'Pay fee balance - All children',
                'account_reference' => 'FAM-' . $familyId,
                'status' => 'active',
                'expires_at' => null,
                'max_uses' => $UNLIMITED_USES,
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
                'metadata' => ['source' => 'family_linked'],
            ]);
        }

        // Expire per-student links for this family so only the family link is used
        \App\Models\PaymentLink::where('family_id', $familyId)
            ->whereNotNull('student_id')
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        return $familyLink;
    }
}

/**
 * Replace placeholders in messages (single, canonical version)
 */
if (!function_exists('replace_placeholders')) {
    function replace_placeholders(string $message, $entity = null, array $extra = []): string
    {
        // Base system placeholders
        $replacements = [
            '{{school_name}}'    => setting('school_name', 'Our School'),
            '{{school_phone}}'   => setting('school_phone', ''),
            '{{school_email}}'   => setting('school_email', ''),
            '{{school_address}}' => setting('school_address', ''),
            '{{term}}'           => setting('current_term', ''),
            '{{academic_year}}'  => setting('current_year', ''),
            '{{date}}'           => now()->format('d M Y'),
            
            // Legacy single brace support
            '{school_name}'    => setting('school_name', 'Our School'),
            '{school_phone}'   => setting('school_phone', ''),
            '{school_email}'   => setting('school_email', ''),
            '{school_address}' => setting('school_address', ''),
            '{term}'           => setting('current_term', ''),
            '{academic_year}'  => setting('current_year', ''),
            '{date}'           => now()->format('d M Y'),
        ];

        // Student-specific placeholders
        if ($entity instanceof \App\Models\Student) {
            $studentName = $entity->full_name ?? $entity->name ?? trim(($entity->first_name ?? '').' '.($entity->last_name ?? ''));
            $admissionNo = $entity->admission_number ?? $entity->admission_no ?? '';
            $className = optional($entity->classroom)->name ?? '';
            $streamName = optional($entity->stream)->name ?? '';
            $classAndStream = trim($className . ($streamName ? ' ' . $streamName : ''));
            $parentName = optional($entity->parent)->father_name
                        ?? optional($entity->parent)->guardian_name
                        ?? optional($entity->parent)->mother_name
                        ?? '';
            $fatherName = optional($entity->parent)->father_name ?? '';
            
            // Get profile update link
            $profileUpdateLink = '';
            if ($entity->family && $entity->family->updateLink && $entity->family->updateLink->is_active) {
                $profileUpdateLink = route('family-update.form', $entity->family->updateLink->token);
            } elseif ($entity->family) {
                // Create link if it doesn't exist
                $link = \App\Models\FamilyUpdateLink::firstOrCreate(
                    ['family_id' => $entity->family->id],
                    ['token' => \App\Models\FamilyUpdateLink::generateToken(), 'is_active' => true]
                );
                $profileUpdateLink = route('family-update.form', $link->token);
            }
            
            $replacements += [
                '{{student_name}}' => $studentName,
                '{{admission_number}}' => $admissionNo,
                '{{admission_no}}' => $admissionNo,
                '{{class_name}}'   => $classAndStream,
                '{{class}}'        => $classAndStream,
                '{{grade}}'        => optional($entity->classroom)->section ?? '',
                '{{parent_name}}'  => $parentName,
                '{{father_name}}'  => $fatherName,
                '{{profile_update_link}}' => $profileUpdateLink,
                
                // Legacy single brace
                '{student_name}' => $studentName,
                '{admission_number}' => $admissionNo,
                '{admission_no}' => $admissionNo,
                '{class_name}'   => $classAndStream,
                '{class}'        => $classAndStream,
                '{grade}'        => optional($entity->classroom)->section ?? '',
                '{parent_name}'  => $parentName,
                '{father_name}'  => $fatherName,
                '{profile_update_link}' => $profileUpdateLink,
            ];

            // Invoice placeholders for student (if not already in $extra)
            if (! isset($extra['outstanding_amount']) && $entity->id && class_exists(\App\Models\Invoice::class)) {
                try {
                    $invoices = \App\Models\Invoice::where('student_id', $entity->id)->get();
                    $totalOutstanding = $invoices->sum(fn ($inv) => max(0, (float) $inv->balance));
                    $latestInvoice = \App\Models\Invoice::where('student_id', $entity->id)
                        ->orderBy('year', 'desc')
                        ->orderBy('term', 'desc')
                        ->first();
                    $replacements['{{outstanding_amount}}'] = number_format(round($totalOutstanding, 2), 2);
                    $replacements['{outstanding_amount}'] = $replacements['{{outstanding_amount}}'];
                    $replacements['{{total_amount}}'] = $latestInvoice ? number_format((float) $latestInvoice->total, 2) : '0.00';
                    $replacements['{total_amount}'] = $replacements['{{total_amount}}'];
                    $replacements['{{invoice_number}}'] = $latestInvoice ? ($latestInvoice->invoice_number ?? 'N/A') : 'N/A';
                    $replacements['{invoice_number}'] = $replacements['{{invoice_number}}'];
                    $replacements['{{due_date}}'] = $latestInvoice && $latestInvoice->due_date ? $latestInvoice->due_date->format('d M Y') : 'N/A';
                    $replacements['{due_date}'] = $replacements['{{due_date}}'];
                } catch (\Throwable $e) {
                    $replacements['{{outstanding_amount}}'] = '0.00';
                    $replacements['{outstanding_amount}'] = '0.00';
                    $replacements['{{total_amount}}'] = '0.00';
                    $replacements['{total_amount}'] = '0.00';
                    $replacements['{{invoice_number}}'] = 'N/A';
                    $replacements['{invoice_number}'] = 'N/A';
                    $replacements['{{due_date}}'] = 'N/A';
                    $replacements['{due_date}'] = 'N/A';
                }
            }

            // Payment link placeholders: use existing link or create one (no expiry, no click limit)
            $paymentLink = get_or_create_payment_link_for_student($entity);
            $paymentLinkUrl = $paymentLink ? $paymentLink->getPaymentUrl() : '';
            $replacements['{{invoice_link}}'] = $paymentLinkUrl;
            $replacements['{invoice_link}'] = $paymentLinkUrl;
            $replacements['{{payment_plan_link}}'] = $paymentLinkUrl;
            $replacements['{payment_plan_link}'] = $paymentLinkUrl;
        } elseif ($entity instanceof \App\Models\Staff) {
            $staffName = $entity->full_name ?? trim(($entity->first_name ?? '').' '.($entity->last_name ?? ''));
            $replacements += [
                '{{staff_name}}' => $staffName,
                '{{role}}'       => $entity->role ?? '',
                '{{experience}}' => $entity->experience ?? '',
                
                // Legacy single brace
                '{staff_name}' => $staffName,
                '{role}'       => $entity->role ?? '',
                '{experience}' => $entity->experience ?? '',
            ];
        } elseif ($entity instanceof \App\Models\ParentInfo) {
            $parentName = $entity->father_name
                        ?? $entity->guardian_name
                        ?? $entity->mother_name
                        ?? '';
            $replacements += [
                '{{parent_name}}' => $parentName,
                '{parent_name}' => $parentName,
            ];
        }

        // Custom placeholders from database (both CommunicationPlaceholder and CustomPlaceholder)
        if (class_exists(\App\Models\CommunicationPlaceholder::class)) {
            try {
                foreach (\App\Models\CommunicationPlaceholder::all() as $ph) {
                    $replacements['{{'.$ph->key.'}}'] = (string) $ph->value;
                    $replacements['{'.$ph->key.'}'] = (string) $ph->value;
                }
            } catch (\Exception $e) {
                // Silently fail if table doesn't exist
            }
        }
        
        if (class_exists(\App\Models\CustomPlaceholder::class)) {
            try {
                foreach (\App\Models\CustomPlaceholder::all() as $ph) {
                    $replacements['{{'.$ph->key.'}}'] = (string) $ph->value;
                    $replacements['{'.$ph->key.'}'] = (string) $ph->value;
                }
            } catch (\Exception $e) {
                // Silently fail if table doesn't exist
            }
        }

        // Merge extra placeholders (highest priority)
        if (!empty($extra)) {
            foreach ($extra as $key => $value) {
                // Support both {{ }} and { } formats
                $replacements['{{'.$key.'}}'] = $value;
                $replacements['{'.$key.'}'] = $value;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}

/**
 * Common placeholder list for help text
 */
if (!function_exists('available_placeholders')) {
    function available_placeholders(): array
    {
        return [
            '{school_name}', '{school_phone}', '{school_email}', '{school_address}', '{term}', '{academic_year}', '{date}',
            '{student_name}', '{admission_no}', '{class_name}', '{class}', '{grade}', '{parent_name}',
            '{staff_name}', '{role}', '{experience}',
        ];
    }
}

if (! function_exists('format_number')) {
    function format_number($value, int $decimals = 0): string
    {
        return number_format((float)$value, $decimals);
    }
}

if (! function_exists('format_money')) {
    function format_money($value, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $currency ?: config('app.currency', 'KES');
        $locale   = $locale   ?: app()->getLocale();

        try {
            if (class_exists(\NumberFormatter::class)) {
                $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
                return $fmt->formatCurrency((float)$value, $currency);
            }
        } catch (\Throwable $e) { /* ignore */ }

        return $currency . ' ' . number_format((float)$value, 2);
    }
}

/**
 * Check if the current user is a supervisor (has subordinates)
 */
if (!function_exists('is_supervisor')) {
    function is_supervisor(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $staff = $user->staff ?? null;
        if (!$staff) return false;
        
        return $staff->subordinates()->exists();
    }
}

/**
 * Get all subordinate staff IDs for the current supervisor
 */
if (!function_exists('get_subordinate_staff_ids')) {
    function get_subordinate_staff_ids(): array
    {
        $user = Auth::user();
        if (!$user) return [];
        
        $staff = $user->staff ?? null;
        if (!$staff) return [];
        
        return $staff->subordinates()->pluck('id')->toArray();
    }
}

/**
 * Get all subordinate staff for the current supervisor
 */
if (!function_exists('get_subordinates')) {
    function get_subordinates()
    {
        $user = Auth::user();
        if (!$user) return collect();
        
        $staff = $user->staff ?? null;
        if (!$staff) return collect();
        
        return $staff->subordinates;
    }
}

/**
 * Get current academic year (as integer)
 */
if (!function_exists('get_current_academic_year')) {
    function get_current_academic_year(): ?int
    {
        $model = get_current_academic_year_model();
        return $model ? (int) $model->year : null;
    }
}

/**
 * Get current academic year model
 */
if (!function_exists('get_current_academic_year_model')) {
    function get_current_academic_year_model(): ?\App\Models\AcademicYear
    {
        // Prefer year tied to the current term; otherwise fall back to manually active, otherwise latest by year
        $term = get_current_term_model();
        if ($term && $term->academic_year_id) {
            return $term->academicYear;
        }

        $manual = \App\Models\AcademicYear::where('is_active', true)->first();
        if ($manual) {
            return $manual;
        }

        return \App\Models\AcademicYear::orderByDesc('year')->first();
    }
}

/**
 * Get current term number (1, 2, or 3)
 */
if (!function_exists('get_current_term_number')) {
    function get_current_term_number(): ?int
    {
        $term = get_current_term_model();
        if (!$term) {
            return null;
        }

        if (preg_match('/\d+/', $term->name, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }
}

/**
 * Get current term model
 */
if (!function_exists('get_current_term_model')) {
    function get_current_term_model(): ?\App\Models\Term
    {
        $tz = config('app.timezone', 'UTC');
        $today = now()->timezone($tz)->toDateString();

        // Prefer date-based determination: opening_date <= today <= closing_date
        $byDate = \App\Models\Term::whereNotNull('opening_date')
            ->whereNotNull('closing_date')
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderBy('opening_date')
            ->first();
        if ($byDate) {
            return $byDate;
        }

        // Fallback to manually flagged current term
        $manual = \App\Models\Term::where('is_current', true)->first();
        if ($manual) {
            return $manual;
        }

        // Fallback: nearest upcoming term, otherwise latest past term
        $upcoming = \App\Models\Term::whereNotNull('opening_date')
            ->whereDate('opening_date', '>', $today)
            ->orderBy('opening_date')
            ->first();
        if ($upcoming) {
            return $upcoming;
        }

        return \App\Models\Term::whereNotNull('closing_date')
            ->orderByDesc('closing_date')
            ->first();
    }
}

/**
 * Get current term ID
 */
if (!function_exists('get_current_term_id')) {
    function get_current_term_id(): ?int
    {
        $term = get_current_term_model();
        return $term ? $term->id : null;
    }
}

/**
 * Get current academic year ID
 */
if (!function_exists('get_current_academic_year_id')) {
    function get_current_academic_year_id(): ?int
    {
        $year = get_current_academic_year_model();
        return $year ? $year->id : null;
    }
}

/**
 * Check if a staff member is supervised by the current user
 */
if (!function_exists('is_my_subordinate')) {
    function is_my_subordinate($staffId): bool
    {
        $subordinateIds = get_subordinate_staff_ids();
        return in_array($staffId, $subordinateIds);
    }
}

/**
 * Get classroom IDs for classes assigned to subordinates
 */
/**
 * Extract local phone number from stored full international format.
 * Removes the country code prefix to show only the local number.
 */
if (!function_exists('extract_local_phone')) {
    function extract_local_phone(?string $fullPhone, ?string $countryCode = '+254'): ?string
    {
        if (!$fullPhone) {
            return null;
        }
        
        // Normalize country code (handle +ke or ke to +254)
        if (strtolower($countryCode) === '+ke' || strtolower($countryCode) === 'ke') {
            $countryCode = '+254';
        }
        $cleanCode = ltrim($countryCode, '+');
        
        // Remove all non-digits from full phone
        $digits = preg_replace('/\D+/', '', $fullPhone);
        
        // If phone starts with country code, remove it
        if (str_starts_with($digits, $cleanCode)) {
            return substr($digits, strlen($cleanCode));
        }
        
        // If it's already just local digits, return as is
        return $digits;
    }
}

if (!function_exists('get_subordinate_classroom_ids')) {
    function get_subordinate_classroom_ids(): array
    {
        $subordinateIds = get_subordinate_staff_ids();
        if (empty($subordinateIds)) return [];
        
        return \DB::table('classroom_subjects')
            ->whereIn('staff_id', $subordinateIds)
            ->distinct()
            ->pluck('classroom_id')
            ->toArray();
    }
}

/**
 * If the exception is due to insufficient SMS credits, flash a warning to the user.
 * Call this in catch blocks after SMS send attempts (web requests only).
 */
if (!function_exists('flash_sms_credit_warning')) {
    function flash_sms_credit_warning(\Throwable $e): void
    {
        if ($e instanceof \App\Exceptions\InsufficientSmsCreditsException) {
            session()->flash('warning', $e->getPublicMessage());
            return;
        }
        if (str_contains($e->getMessage(), 'Insufficient SMS credits')) {
            session()->flash('warning', 'SMS could not be sent: insufficient SMS credits. Please top up your SMS balance or check Communication → Logs for details.');
        }
    }
}
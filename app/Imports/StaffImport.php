<?php

namespace App\Imports;

use App\Models\Staff;
use App\Models\Setting;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\StaffCategory;
use App\Models\User;
use App\Models\CommunicationTemplate;
use App\Services\CommunicationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Spatie\Permission\Models\Role;

class StaffImport implements ToCollection
{
    public int $successCount = 0;
    public array $errorMessages = [];

    public function collection(Collection $rows)
    {
        // Remove header if present (first row might be empty or header)
        $firstRow = $rows->first();
        // If first row is empty or looks like a header, remove it
        if ($firstRow && ($firstRow->isEmpty() || 
            (isset($firstRow[0]) && (empty($firstRow[0]) || strtolower($firstRow[0]) === 'staff_id' || strtolower($firstRow[0]) === 'first_name')))) {
            $rows->shift();
        }

        // Map column index → key
        // Keep aligned with headings above
        foreach ($rows as $i => $row) {
            try {
                $data = [
                    'staff_id'   => trim((string)($row[0] ?? '')),
                    'first_name' => trim((string)($row[1] ?? '')),
                    'middle_name'=> trim((string)($row[2] ?? '')),
                    'last_name'  => trim((string)($row[3] ?? '')),
                    'work_email' => trim((string)($row[4] ?? '')),
                    'personal_email' => trim((string)($row[5] ?? '')),
                    'phone_number' => trim((string)($row[6] ?? '')),
                    'id_number'    => trim((string)($row[7] ?? '')),
                    'date_of_birth'=> $this->parseDate($row[8] ?? null),
                    'gender'       => trim((string)($row[9] ?? '')),
                    'marital_status'=> trim((string)($row[10] ?? '')),
                    'residential_address' => trim((string)($row[11] ?? '')),
                    'emergency_contact_name' => trim((string)($row[12] ?? '')),
                    'emergency_contact_relationship' => trim((string)($row[13] ?? '')),
                    'emergency_contact_phone' => trim((string)($row[14] ?? '')),
                    'kra_pin'  => trim((string)($row[15] ?? '')),
                    'nssf'     => trim((string)($row[16] ?? '')),
                    'nhif'     => trim((string)($row[17] ?? '')),
                    'bank_name'=> trim((string)($row[18] ?? '')),
                    'bank_branch'=> trim((string)($row[19] ?? '')),
                    'bank_account'=> trim((string)($row[20] ?? '')),
                    'department_name' => trim((string)($row[21] ?? '')),
                    'job_title_name'  => trim((string)($row[22] ?? '')),
                    'category_name'   => trim((string)($row[23] ?? '')),
                    'supervisor_staff_id' => trim((string)($row[24] ?? '')),
                    'spatie_role_name'    => trim((string)($row[25] ?? '')),
                ];
                $rawPhone = $data['phone_number'];
                $rawEmergency = $data['emergency_contact_phone'];
                $phoneService = app(\App\Services\PhoneNumberService::class);
                $data['phone_number'] = $phoneService->formatWithCountryCode($data['phone_number'] ?? null, '+254');
                $data['emergency_contact_phone'] = $phoneService->formatWithCountryCode($data['emergency_contact_phone'] ?? null, '+254');

                // Required checks
                if (!$data['first_name'] || !$data['last_name'] || !$data['work_email'] || !$data['phone_number'] || !$data['id_number']) {
                    throw new \RuntimeException("Row ".($i+2).": Missing required fields (first_name, last_name, work_email, phone_number, id_number).");
                }

                // Resolve lookup IDs (create if missing)
                $departmentId = null;
                if ($data['department_name']) {
                    $departmentId = Department::firstOrCreate(['name'=>$data['department_name']])->id;
                }
                $jobTitleId = null;
                if ($data['job_title_name']) {
                    $jobTitleId = JobTitle::firstOrCreate(['name'=>$data['job_title_name']])->id;
                }
                $categoryId = null;
                if ($data['category_name']) {
                    $categoryId = StaffCategory::firstOrCreate(['name'=>$data['category_name']])->id;
                }
                $supervisorId = null;
                if ($data['supervisor_staff_id']) {
                    $supervisorId = Staff::where('staff_id',$data['supervisor_staff_id'])->value('id');
                }

                // Staff ID
                $staffId = $data['staff_id'];
                if (!$staffId) {
                    $prefix = Setting::get('staff_id_prefix', 'STAFF');
                    $start  = Setting::getInt('staff_id_start', 1001);
                    $staffId = $prefix . $start;
                    Setting::setInt('staff_id_start', $start + 1);
                }

                // Create user (login via work_email)
                if (User::where('email',$data['work_email'])->exists()) {
                    throw new \RuntimeException("Row ".($i+2).": work_email already exists in users.");
                }
                if (Staff::where('work_email',$data['work_email'])->exists()) {
                    throw new \RuntimeException("Row ".($i+2).": work_email already exists in staff.");
                }

                $createdStaff = null;
                $createdUser = null;
                $passwordPlain = $data['id_number'];

                DB::transaction(function () use ($data, $departmentId, $jobTitleId, $categoryId, $supervisorId, $staffId, &$i, &$createdStaff, &$createdUser, $passwordPlain) {
                    $createdUser = User::create([
                        'name'                 => $data['first_name'].' '.$data['last_name'],
                        'email'                => $data['work_email'],
                        'password'             => Hash::make($passwordPlain),
                        'must_change_password' => true,
                    ]);

                    if ($data['spatie_role_name']) {
                        if ($role = Role::where('name', $data['spatie_role_name'])->first()) {
                            $createdUser->assignRole($role->name);
                        }
                    } else {
                        // Auto-role by category if none selected
                        if ($categoryId == 1) {
                            $createdUser->assignRole('Teacher');
                        } elseif ($categoryId == 2) {
                            $createdUser->assignRole('Administrator');
                        } else {
                            $createdUser->assignRole('Staff');
                        }
                    }

                    $createdStaff = Staff::create([
                        'user_id'    => $createdUser->id,
                        'staff_id'   => $staffId,
                        'first_name' => $data['first_name'],
                        'middle_name'=> $data['middle_name'] ?: null,
                        'last_name'  => $data['last_name'],
                        'work_email' => $data['work_email'],
                        'personal_email' => $data['personal_email'] ?: null,
                        'phone_number'   => $data['phone_number'],
                        'id_number'      => $data['id_number'],
                        'date_of_birth'  => $data['date_of_birth'] ?: null,
                        'gender'         => $data['gender'] ?: null,
                        'marital_status' => $data['marital_status'] ?: null,
                        'residential_address' => $data['residential_address'] ?: null,
                        'emergency_contact_name' => $data['emergency_contact_name'] ?: null,
                        'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?: null,
                        'emergency_contact_phone' => $data['emergency_contact_phone'] ?: null,
                        'kra_pin'      => $data['kra_pin'] ?: null,
                        'nssf'         => $data['nssf'] ?: null,
                        'nhif'         => $data['nhif'] ?: null,
                        'bank_name'    => $data['bank_name'] ?: null,
                        'bank_branch'  => $data['bank_branch'] ?: null,
                        'bank_account' => $data['bank_account'] ?: null,
                        'department_id'=> $departmentId,
                        'job_title_id' => $jobTitleId,
                        'staff_category_id' => $categoryId,
                        'supervisor_id'=> $supervisorId,
                        'status' => 'active',
                    ]);
                });

                if ($createdStaff) {
                    $logger = app(\App\Services\PhoneNumberNormalizationLogger::class);
                    $logger->logIfChanged(Staff::class, $createdStaff->id, 'phone_number', $rawPhone, $data['phone_number'] ?? null, '+254', 'staff_import', null);
                    $logger->logIfChanged(Staff::class, $createdStaff->id, 'emergency_contact_phone', $rawEmergency, $data['emergency_contact_phone'] ?? null, '+254', 'staff_import', null);
                }

                // Send welcome notifications after successful creation
                if ($createdStaff && $createdUser) {
                    $this->sendWelcomeNotifications($createdStaff, $createdUser, $passwordPlain);
                }

                $this->successCount++;
            } catch (\Throwable $e) {
                $this->errorMessages[] = $e->getMessage();
            }
        }
    }

    /**
     * Parse date from various formats (Excel serial, string, etc.)
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // If it's a numeric value, it might be an Excel serial date
        if (is_numeric($value)) {
            try {
                // Excel serial date to date conversion
                $unixTimestamp = ($value - 25569) * 86400;
                return gmdate('Y-m-d', $unixTimestamp);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Try to parse as a date string
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Send welcome email and SMS notifications to newly created staff
     */
    private function sendWelcomeNotifications(Staff $staff, User $user, string $passwordPlain): void
    {
        try {
            $comm = app(CommunicationService::class);
            
            // Get school settings for templates
            $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
            $appUrl = config('app.url');
            
            // Prepare template variables matching CommunicationTemplateSeeder placeholders
            $vars = [
                'staff_name' => $user->name,
                'school_name' => $schoolName,
                'app_url' => $appUrl,
                'login_email' => $user->email,
                'temporary_password' => $passwordPlain,
                'staff_role' => $staff->jobTitle->name ?? $staff->category->name ?? 'Staff Member',
                // Legacy support for old template format
                'name'     => $user->name,
                'login'    => $user->email,
                'password' => $passwordPlain,
                'staff_id' => $staff->staff_id,
            ];

            // Get welcome templates (updated codes to match StaffController)
            $emailTpl = CommunicationTemplate::where('code', 'staff_welcome_email')->first();
            $smsTpl   = CommunicationTemplate::where('code', 'staff_welcome_sms')->first();

            // Send email notification
            if ($emailTpl && $user->email) {
                try {
                    $subject = $this->fillTemplate($emailTpl->subject ?? 'Welcome to ' . config('app.name'), $vars);
                    $body    = $this->fillTemplate($emailTpl->content, $vars);
                    $attachmentPath = $emailTpl->attachment ?? null;

                    $comm->sendEmail(
                        'staff',
                        $staff->id,
                        $user->email,
                        $subject,
                        $body,
                        $attachmentPath
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send welcome email to staff during import', [
                        'staff_id' => $staff->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send SMS notification
            if ($smsTpl && $staff->phone_number) {
                try {
                    $smsBody = $this->fillTemplate($smsTpl->content, $vars);
                    $smsTitle = $smsTpl->title ? $this->fillTemplate($smsTpl->title, $vars) : 'Welcome to ' . config('app.name');
                    $comm->sendSMS('staff', $staff->id, $staff->phone_number, $smsBody, $smsTitle);
                } catch (\Exception $e) {
                    Log::warning('Failed to send welcome SMS to staff during import', [
                        'staff_id' => $staff->id,
                        'phone' => $staff->phone_number,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome notifications during staff import', [
                'staff_id' => $staff->id ?? null,
                'error' => $e->getMessage()
            ]);
            // Don't throw - notification failures shouldn't break import
        }
    }

    /**
     * Fill template with variables
     */
    private function fillTemplate(string $content, array $vars): string
    {
        $search  = array_map(fn($k) => '{' . $k . '}', array_keys($vars));
        $replace = array_values($vars);
        return str_replace($search, $replace, $content);
    }
}

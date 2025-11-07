<?php

namespace App\Imports;

use App\Models\Staff;
use App\Models\SystemSetting;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\StaffCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Spatie\Permission\Models\Role;

class StaffImport implements ToCollection
{
    public int $successCount = 0;
    public array $errorMessages = [];

    public function collection(Collection $rows)
    {
        // Remove header
        $header = $rows->shift();

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
                    'date_of_birth'=> $row[8] ?? null,
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
                    $prefix = SystemSetting::getValue('staff_id_prefix', 'STAFF');
                    $start  = (int) SystemSetting::getValue('staff_id_start', 1001);
                    $staffId = $prefix.$start;
                    SystemSetting::set('staff_id_start', $start + 1);
                }

                // Create user (login via work_email)
                if (User::where('email',$data['work_email'])->exists()) {
                    throw new \RuntimeException("Row ".($i+2).": work_email already exists in users.");
                }
                if (Staff::where('work_email',$data['work_email'])->exists()) {
                    throw new \RuntimeException("Row ".($i+2).": work_email already exists in staff.");
                }

                DB::transaction(function () use ($data, $departmentId, $jobTitleId, $categoryId, $supervisorId, $staffId, &$i) {
                    $passwordPlain = $data['id_number'];

                    $user = User::create([
                        'name'                 => $data['first_name'].' '.$data['last_name'],
                        'email'                => $data['work_email'],
                        'password'             => Hash::make($passwordPlain),
                        'must_change_password' => true,
                    ]);

                    if ($data['spatie_role_name']) {
                        if ($role = Role::where('name', $data['spatie_role_name'])->first()) {
                            $user->assignRole($role->name);
                        }
                    }

                    Staff::create([
                        'user_id'    => $user->id,
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

                $this->successCount++;
            } catch (\Throwable $e) {
                $this->errorMessages[] = $e->getMessage();
            }
        }
    }
}

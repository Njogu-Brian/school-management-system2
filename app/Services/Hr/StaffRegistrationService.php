<?php

namespace App\Services\Hr;

use App\Models\Department;
use App\Models\JobTitle;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffCategory;
use App\Models\StaffRegistration;
use App\Models\User;
use App\Services\PhoneNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StaffRegistrationService
{
    public function __construct(protected PhoneNumberService $phones) {}

    public function isOpen(): bool
    {
        $value = Setting::get('staff_registration_open', '1');

        return $value === null || $value === '' || $value === '1' || $value === 'true';
    }

    public function submit(array $data, ?string $ip = null, ?UploadedFile $photo = null): StaffRegistration
    {
        $idNumber = trim((string) $data['id_number']);
        $email = strtolower(trim((string) $data['personal_email']));

        $duplicate = StaffRegistration::query()
            ->where('status', StaffRegistration::STATUS_PENDING)
            ->where(function ($q) use ($idNumber, $email) {
                $q->where('id_number', $idNumber)->orWhere('personal_email', $email);
            })
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'id_number' => 'An application with this ID number or email is already pending review.',
            ]);
        }

        if (Staff::query()->where('id_number', $idNumber)->exists()) {
            throw ValidationException::withMessages([
                'id_number' => 'A staff member with this ID number already exists.',
            ]);
        }

        $photoPath = null;
        if ($photo) {
            $photoPath = $photo->store('staff_registration_photos', config('filesystems.public_disk', 'public'));
        }

        return StaffRegistration::create([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'marital_status' => $data['marital_status'] ?? null,
            'residential_address' => $data['residential_address'] ?? null,
            'id_number' => $idNumber,
            'personal_email' => $email,
            'photo' => $photoPath,
            'phone_number' => $this->phones->formatWithCountryCode($data['phone_number'] ?? null, '+254'),
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
            'emergency_contact_phone' => $this->phones->formatWithCountryCode($data['emergency_contact_phone'] ?? null, '+254'),
            'kra_pin' => $data['kra_pin'] ?? null,
            'nssf' => $data['nssf'] ?? null,
            'nhif' => $data['nhif'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_branch' => $data['bank_branch'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'department_id' => $this->nullableId($data['department_id'] ?? null, Department::class),
            'job_title_id' => $this->nullableId($data['job_title_id'] ?? null, JobTitle::class),
            'staff_category_id' => $this->nullableId($data['staff_category_id'] ?? null, StaffCategory::class),
            'hire_date' => $data['hire_date'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'full_time',
            'contract_start_date' => $data['contract_start_date'] ?? null,
            'contract_end_date' => $data['contract_end_date'] ?? null,
            'max_lessons_per_week' => $data['max_lessons_per_week'] ?? null,
            'status' => StaffRegistration::STATUS_PENDING,
            'ip_address' => $ip,
        ]);
    }

    public function suggestWorkEmail(string $firstName, string $lastName): string
    {
        $domain = ltrim((string) Setting::get('staff_email_domain', 'royalkingsschools.sc.ke'), '@');
        $first = strtolower(preg_replace('/[^a-z0-9]/i', '', $firstName) ?? '');
        $last = strtolower(preg_replace('/[^a-z0-9]/i', '', $lastName) ?? '');
        $initial = $first !== '' ? $first[0] : 's';
        $base = ($last !== '' ? $initial.'.'.$last : $initial.'.staff');

        $candidate = $base.'@'.$domain;
        $n = 2;
        while (User::where('email', $candidate)->exists() || Staff::where('work_email', $candidate)->exists()) {
            $candidate = $base.$n.'@'.$domain;
            $n++;
        }

        return $candidate;
    }

    public function allocateStaffId(): string
    {
        $prefix = (string) Setting::get('staff_id_prefix', 'STAFF');
        $start = Setting::getInt('staff_id_start', 1001);

        do {
            $staffId = $prefix.$start;
            $start++;
        } while (Staff::where('staff_id', $staffId)->exists());

        Setting::setInt('staff_id_start', $start);

        return $staffId;
    }

    /**
     * @param  array{work_email?:string,department_id?:int|null,job_title_id?:int|null,staff_category_id?:int|null,spatie_role_id?:int|null}  $hr
     */
    public function approve(StaffRegistration $registration, array $hr, User $reviewer): Staff
    {
        if (! $registration->isPending()) {
            throw ValidationException::withMessages(['status' => 'This application has already been reviewed.']);
        }

        return DB::transaction(function () use ($registration, $hr, $reviewer) {
            $workEmail = strtolower(trim((string) ($hr['work_email'] ?? $this->suggestWorkEmail($registration->first_name, $registration->last_name))));

            if (User::where('email', $workEmail)->exists() || Staff::where('work_email', $workEmail)->exists()) {
                throw ValidationException::withMessages([
                    'work_email' => 'This work email is already in use.',
                ]);
            }

            $user = User::create([
                'name' => $registration->full_name,
                'email' => $workEmail,
                'password' => $registration->id_number,
                'phone_number' => $registration->phone_number,
                'must_change_password' => false,
            ]);

            $role = $this->resolveRole($hr['spatie_role_id'] ?? null);
            $user->assignRole($role);

            $staff = Staff::create([
                'user_id' => $user->id,
                'staff_id' => $this->allocateStaffId(),
                'first_name' => $registration->first_name,
                'middle_name' => $registration->middle_name,
                'last_name' => $registration->last_name,
                'work_email' => $workEmail,
                'personal_email' => $registration->personal_email,
                'photo' => $this->copyPhotoToStaff($registration),
                'phone_number' => $registration->phone_number,
                'emergency_contact_name' => $registration->emergency_contact_name,
                'emergency_contact_relationship' => $registration->emergency_contact_relationship,
                'emergency_contact_phone' => $registration->emergency_contact_phone,
                'id_number' => $registration->id_number,
                'date_of_birth' => $registration->date_of_birth,
                'gender' => $registration->gender,
                'marital_status' => $registration->marital_status,
                'residential_address' => $registration->residential_address,
                'kra_pin' => $registration->kra_pin,
                'nssf' => $registration->nssf,
                'nhif' => $registration->nhif,
                'bank_name' => $registration->bank_name,
                'bank_branch' => $registration->bank_branch,
                'bank_account' => $registration->bank_account,
                'payment_method' => $registration->payment_method ?: 'bank',
                'department_id' => $this->nullableId($hr['department_id'] ?? $registration->department_id, Department::class),
                'job_title_id' => $this->nullableId($hr['job_title_id'] ?? $registration->job_title_id, JobTitle::class)
                    ?? JobTitle::query()->where('name', 'Teacher')->value('id'),
                'staff_category_id' => $this->nullableId($hr['staff_category_id'] ?? $registration->staff_category_id, StaffCategory::class)
                    ?? StaffCategory::query()->where('name', 'Teaching')->value('id'),
                'max_lessons_per_week' => $registration->max_lessons_per_week,
                'status' => 'active',
                'employment_status' => 'active',
                'employment_type' => $registration->employment_type ?: 'full_time',
                'hire_date' => $registration->hire_date?->toDateString() ?: now()->toDateString(),
                'contract_start_date' => $registration->contract_start_date,
                'contract_end_date' => $registration->contract_end_date,
            ]);

            $registration->update([
                'status' => StaffRegistration::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'staff_id' => $staff->id,
            ]);

            return $staff;
        });
    }

    public function reject(StaffRegistration $registration, string $reason, User $reviewer): void
    {
        if (! $registration->isPending()) {
            throw ValidationException::withMessages(['status' => 'This application has already been reviewed.']);
        }

        $registration->update([
            'status' => StaffRegistration::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    protected function copyPhotoToStaff(StaffRegistration $registration): ?string
    {
        if (! $registration->photo) {
            return null;
        }

        $disk = config('filesystems.public_disk', 'public');
        if (! Storage::disk($disk)->exists($registration->photo)) {
            return $registration->photo;
        }

        $newPath = 'staff_photos/'.basename($registration->photo);
        Storage::disk($disk)->copy($registration->photo, $newPath);

        return $newPath;
    }

    protected function resolveRole(mixed $roleId): Role
    {
        if ($roleId) {
            $role = Role::query()->where('id', (int) $roleId)->where('guard_name', 'web')->first();
            if ($role) {
                return $role;
            }
        }

        return Role::query()
            ->whereRaw('LOWER(name) = ?', ['teacher'])
            ->where('guard_name', 'web')
            ->first()
            ?? Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
    }

    protected function nullableId(mixed $id, string $model): ?int
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        return $model::query()->whereKey($id)->exists() ? $id : null;
    }
}

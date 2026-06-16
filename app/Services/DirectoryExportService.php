<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DirectoryExportService
{
  public function studentFieldGroups(): array
  {
    return [
      'Student' => [
        'admission_number' => 'Admission Number',
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'last_name' => 'Last Name',
        'full_name' => 'Full Name',
        'gender' => 'Gender',
        'dob' => 'Date of Birth',
        'classroom' => 'Class',
        'stream' => 'Stream',
        'category' => 'Category',
        'nemis_number' => 'NEMIS Number',
        'knec_assessment_number' => 'KNEC Assessment Number',
        'religion' => 'Religion',
        'residential_area' => 'Residential Area',
        'admission_date' => 'Admission Date',
        'enrollment_year' => 'Enrollment Year',
        'status' => 'Status',
      ],
      'Parents / Guardians' => [
        'father_name' => 'Father Name',
        'father_phone' => 'Father Phone',
        'father_email' => 'Father Email',
        'mother_name' => 'Mother Name',
        'mother_phone' => 'Mother Phone',
        'mother_email' => 'Mother Email',
        'guardian_name' => 'Guardian Name',
        'guardian_phone' => 'Guardian Phone',
        'guardian_email' => 'Guardian Email',
      ],
      'Emergency & Health' => [
        'emergency_contact_name' => 'Emergency Contact Name',
        'emergency_contact_phone' => 'Emergency Contact Phone',
        'allergies' => 'Allergies',
        'chronic_conditions' => 'Chronic Conditions',
        'has_special_needs' => 'Has Special Needs',
        'special_needs_description' => 'Special Needs Description',
      ],
      'Other' => [
        'drop_off_point' => 'Drop-off Point',
        'previous_schools' => 'Previous Schools',
        'is_alumni' => 'Alumni',
        'alumni_date' => 'Alumni Date',
      ],
    ];
  }

  public function staffFieldGroups(): array
  {
    return [
      'Personal' => [
        'staff_id' => 'Staff ID',
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'last_name' => 'Last Name',
        'full_name' => 'Full Name',
        'id_number' => 'ID Number',
        'date_of_birth' => 'Date of Birth',
        'gender' => 'Gender',
        'marital_status' => 'Marital Status',
        'residential_address' => 'Residential Address',
      ],
      'Contact' => [
        'work_email' => 'Work Email',
        'personal_email' => 'Personal Email',
        'phone_number' => 'Phone Number',
        'emergency_contact_name' => 'Emergency Contact Name',
        'emergency_contact_phone' => 'Emergency Contact Phone',
        'emergency_contact_relationship' => 'Emergency Contact Relationship',
      ],
      'Employment' => [
        'department' => 'Department',
        'job_title' => 'Job Title',
        'category' => 'Category',
        'supervisor' => 'Supervisor',
        'status' => 'Status',
        'employment_status' => 'Employment Status',
        'employment_type' => 'Employment Type',
        'hire_date' => 'Hire Date',
        'termination_date' => 'Termination Date',
        'contract_start_date' => 'Contract Start',
        'contract_end_date' => 'Contract End',
      ],
      'Payroll & Banking' => [
        'kra_pin' => 'KRA PIN',
        'nssf' => 'NSSF',
        'nhif' => 'NHIF',
        'bank_name' => 'Bank Name',
        'bank_branch' => 'Bank Branch',
        'bank_account' => 'Bank Account',
        'basic_salary' => 'Basic Salary',
      ],
    ];
  }

  public function defaultStudentFields(): array
  {
    return ['admission_number', 'first_name', 'middle_name', 'last_name', 'gender', 'dob', 'classroom', 'stream'];
  }

  public function defaultStaffFields(): array
  {
    return ['staff_id', 'first_name', 'middle_name', 'last_name', 'work_email', 'phone_number', 'department', 'job_title'];
  }

  public function resolveFields(string $type, array $requested): array
  {
    $catalog = $type === 'staff'
      ? $this->flattenFieldGroups($this->staffFieldGroups())
      : $this->flattenFieldGroups($this->studentFieldGroups());

    $fields = array_values(array_intersect($requested, array_keys($catalog)));

    if (empty($fields)) {
      return $type === 'staff' ? $this->defaultStaffFields() : $this->defaultStudentFields();
    }

    return $fields;
  }

  public function fieldLabels(string $type, array $fields): array
  {
    $catalog = $type === 'staff'
      ? $this->flattenFieldGroups($this->staffFieldGroups())
      : $this->flattenFieldGroups($this->studentFieldGroups());

    return array_map(fn ($key) => $catalog[$key] ?? $key, $fields);
  }

  public function studentQuery(Request $request): Builder
  {
    $relations = ['parent', 'classroom', 'stream', 'category', 'dropOffPoint'];

    if ($request->boolean('archived_only')) {
      $query = Student::withArchived()
        ->with($relations)
        ->where('archive', 1)
        ->where('is_alumni', false);
    } elseif ($request->boolean('show_alumni')) {
      $query = Student::withArchived()
        ->with($relations)
        ->where('is_alumni', true);
    } elseif ($request->has('showArchived')) {
      $query = Student::withArchived()->with($relations);
    } else {
      $query = Student::with($relations)->where('archive', 0);
    }

    if ($request->filled('name')) {
      $searchTerm = '%' . addcslashes($request->name, '%_\\') . '%';
      $query->where(fn ($q) => $q->where('first_name', 'like', $searchTerm)
        ->orWhere('middle_name', 'like', $searchTerm)
        ->orWhere('last_name', 'like', $searchTerm));
    }

    if ($request->filled('admission_number')) {
      $searchTerm = '%' . addcslashes($request->admission_number, '%_\\') . '%';
      $query->where('admission_number', 'like', $searchTerm);
    }

    if ($request->filled('classroom_id')) {
      $query->where('classroom_id', $request->classroom_id);
    }

    if ($request->filled('stream_id')) {
      $query->where('stream_id', $request->stream_id);
    }

    return $query->orderBy('first_name')->orderBy('last_name');
  }

  public function staffQuery(Request $request): Builder
  {
    $query = Staff::with(['department', 'jobTitle', 'category', 'supervisor']);

    if ($request->filled('q')) {
      $search = $request->q;
      $query->where(function ($q) use ($search) {
        $q->where('first_name', 'like', "%{$search}%")
          ->orWhere('last_name', 'like', "%{$search}%")
          ->orWhere('middle_name', 'like', "%{$search}%")
          ->orWhere('work_email', 'like', "%{$search}%")
          ->orWhere('personal_email', 'like', "%{$search}%")
          ->orWhere('phone_number', 'like', "%{$search}%")
          ->orWhere('staff_id', 'like', "%{$search}%")
          ->orWhere('id_number', 'like', "%{$search}%");
      });
    }

    if ($request->filled('department_id')) {
      $query->where('department_id', $request->department_id);
    }

    if ($request->filled('status')) {
      $status = $request->status;
      if (in_array($status, ['active', 'archived'], true)) {
        $query->where('status', $status);
      }
    } elseif (!$request->boolean('include_archived')) {
      $query->where('status', 'active');
    }

    return $query->orderBy('first_name')->orderBy('last_name');
  }

  /**
   * @param  iterable<Student>  $students
   */
  public function buildStudentRows(iterable $students, array $fields): array
  {
    $rows = [];
    foreach ($students as $student) {
      $rows[] = $this->extractStudentRow($student, $fields);
    }

    return $rows;
  }

  /**
   * @param  iterable<Staff>  $staff
   */
  public function buildStaffRows(iterable $staff, array $fields): array
  {
    $rows = [];
    foreach ($staff as $member) {
      $rows[] = $this->extractStaffRow($member, $fields);
    }

    return $rows;
  }

  public function extractStudentRow(Student $student, array $fields): array
  {
    $parent = $student->parent;
    $values = [
      'admission_number' => $student->admission_number,
      'first_name' => $student->first_name,
      'middle_name' => $student->middle_name,
      'last_name' => $student->last_name,
      'full_name' => $student->full_name,
      'gender' => $student->gender,
      'dob' => $this->formatDate($student->dob),
      'classroom' => $student->classroom?->name,
      'stream' => $student->stream?->name,
      'category' => $student->category?->name,
      'nemis_number' => $student->nemis_number,
      'knec_assessment_number' => $student->knec_assessment_number,
      'religion' => $student->religion,
      'residential_area' => $student->residential_area,
      'admission_date' => $this->formatDate($student->admission_date),
      'enrollment_year' => $student->enrollment_year,
      'status' => $student->status,
      'father_name' => $parent?->father_name,
      'father_phone' => $parent?->father_phone,
      'father_email' => $parent?->father_email,
      'mother_name' => $parent?->mother_name,
      'mother_phone' => $parent?->mother_phone,
      'mother_email' => $parent?->mother_email,
      'guardian_name' => $parent?->guardian_name,
      'guardian_phone' => $parent?->guardian_phone,
      'guardian_email' => $parent?->guardian_email,
      'emergency_contact_name' => $student->emergency_contact_name,
      'emergency_contact_phone' => $student->emergency_contact_phone,
      'allergies' => $student->allergies,
      'chronic_conditions' => $student->chronic_conditions,
      'has_special_needs' => $this->formatBool($student->has_special_needs),
      'special_needs_description' => $student->special_needs_description,
      'drop_off_point' => $student->dropOffPoint?->name ?? $student->drop_off_point_other ?? $student->drop_off_point,
      'previous_schools' => $student->previous_schools,
      'is_alumni' => $this->formatBool($student->is_alumni),
      'alumni_date' => $this->formatDate($student->alumni_date),
    ];

    return array_map(fn ($key) => $values[$key] ?? '', $fields);
  }

  public function extractStaffRow(Staff $staff, array $fields): array
  {
    $values = [
      'staff_id' => $staff->staff_id,
      'first_name' => $staff->first_name,
      'middle_name' => $staff->middle_name,
      'last_name' => $staff->last_name,
      'full_name' => $staff->full_name,
      'id_number' => $staff->id_number,
      'date_of_birth' => $this->formatDate($staff->date_of_birth),
      'gender' => $staff->gender,
      'marital_status' => $staff->marital_status,
      'residential_address' => $staff->residential_address,
      'work_email' => $staff->work_email,
      'personal_email' => $staff->personal_email,
      'phone_number' => $staff->phone_number,
      'emergency_contact_name' => $staff->emergency_contact_name,
      'emergency_contact_phone' => $staff->emergency_contact_phone,
      'emergency_contact_relationship' => $staff->emergency_contact_relationship,
      'department' => $staff->department?->name,
      'job_title' => $staff->jobTitle?->name,
      'category' => $staff->category?->name,
      'supervisor' => $staff->supervisor?->full_name,
      'status' => ucfirst((string) $staff->status),
      'employment_status' => ucfirst(str_replace('_', ' ', (string) ($staff->employment_status ?? ''))),
      'employment_type' => ucfirst(str_replace('_', ' ', (string) ($staff->employment_type ?? ''))),
      'hire_date' => $this->formatDate($staff->hire_date),
      'termination_date' => $this->formatDate($staff->termination_date),
      'contract_start_date' => $this->formatDate($staff->contract_start_date),
      'contract_end_date' => $this->formatDate($staff->contract_end_date),
      'kra_pin' => $staff->kra_pin,
      'nssf' => $staff->nssf,
      'nhif' => $staff->nhif,
      'bank_name' => $staff->bank_name,
      'bank_branch' => $staff->bank_branch,
      'bank_account' => $staff->bank_account,
      'basic_salary' => $staff->basic_salary !== null ? number_format((float) $staff->basic_salary, 2) : '',
    ];

    return array_map(fn ($key) => $values[$key] ?? '', $fields);
  }

  public function filterSummary(string $type, Request $request): string
  {
    $parts = [];

    if ($type === 'students') {
      if ($request->filled('name')) {
        $parts[] = 'Name: ' . $request->name;
      }
      if ($request->filled('admission_number')) {
        $parts[] = 'Admission #: ' . $request->admission_number;
      }
      if ($request->filled('classroom_id')) {
        $parts[] = 'Class ID: ' . $request->classroom_id;
      }
      if ($request->filled('stream_id')) {
        $parts[] = 'Stream ID: ' . $request->stream_id;
      }
      if ($request->has('showArchived')) {
        $parts[] = 'Including archived';
      }
      if ($request->boolean('show_alumni')) {
        $parts[] = 'Alumni only';
      }
      if ($request->boolean('archived_only')) {
        $parts[] = 'Archived only';
      }
    } else {
      if ($request->filled('q')) {
        $parts[] = 'Search: ' . $request->q;
      }
      if ($request->filled('department_id')) {
        $parts[] = 'Department ID: ' . $request->department_id;
      }
      if ($request->filled('status')) {
        $parts[] = 'Status: ' . $request->status;
      } elseif ($request->boolean('include_archived')) {
        $parts[] = 'Including archived';
      }
    }

    return $parts ? implode(' · ', $parts) : 'All records';
  }

  protected function flattenFieldGroups(array $groups): array
  {
    $flat = [];
    foreach ($groups as $fields) {
      foreach ($fields as $key => $label) {
        $flat[$key] = $label;
      }
    }

    return $flat;
  }

  protected function formatDate($value): string
  {
    if ($value === null || $value === '') {
      return '';
    }

    if ($value instanceof Carbon) {
      return $value->format('Y-m-d');
    }

    try {
      return Carbon::parse($value)->format('Y-m-d');
    } catch (\Throwable) {
      return (string) $value;
    }
  }

  protected function formatBool($value): string
  {
    if ($value === null || $value === '') {
      return '';
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
  }
}

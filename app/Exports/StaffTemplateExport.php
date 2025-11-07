<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        
        return [
            'first_name',
            'middle_name',
            'last_name',
            'work_email',             // login email
            'personal_email',
            'phone_number',
            'id_number',
            'date_of_birth',          // YYYY-MM-DD
            'gender',                 // male|female|other
            'marital_status',         // single|married|...
            'residential_address',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_phone',
            'kra_pin',
            'nssf',
            'nhif',
            'bank_name',
            'bank_branch',
            'bank_account',
            'department_id',          // FK (optional)
            'job_title_id',           // FK (optional)
            'staff_category_id',      // FK (optional)
            'supervisor_id',          // FK (optional)
            'spatie_role_id',         // FK to spatie roles (optional)
        ];
    }

    public function array(): array
    {
        // Provide one or two sample rows (optional)
        return [
            [
                'Jane','A.','Doe','jane.doe@school.ac.ke','jane.personal@mail.com',
                '+254712345678','12345678','1990-01-01','female','single','Nairobi, Kenya',
                'John Doe','Brother','+254700000000','A1234567','123456','NHIF123',
                'Equity','Main','0123456789', '', '', '', '', ''
            ],
        ];
    }
}

<?php

namespace App\Imports;

use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StaffImport implements ToCollection, WithHeadingRow
{
    public $successCount = 0;
    public $errorMessages = [];
    public function collection(Collection $rows)
    {
        $controller = App::make(\App\Http\Controllers\StaffController::class);

        foreach ($rows as $index => $row) {
            try {
                DB::beginTransaction();

                $roleNames = array_map('trim', explode(',', $row['roles']));
                $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->toArray();

                if (empty($row['email']) || empty($row['first_name']) || empty($row['last_name']) || empty($roleIds)) {
                    $this->errorMessages[] = "Row " . ($index + 2) . ": Missing required data or invalid roles.";
                    DB::rollBack();
                    continue;
                }

                $dob = null;
                if (!empty($row['date_of_birth'])) {
                    if (is_numeric($row['date_of_birth'])) {
                        $dob = Date::excelToDateTimeObject($row['date_of_birth'])->format('Y-m-d');
                    } else {
                        $dob = \Carbon\Carbon::parse($row['date_of_birth'])->format('Y-m-d');
                    }
                }

                $request = Request::create('/fake-staff-upload', 'POST', [
                    'email' => $row['email'],
                    'roles' => $roleIds,
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'],
                    'last_name' => $row['last_name'],
                    'phone_number' => $row['phone_number'],
                    'id_number' => $row['id_number'],
                    'date_of_birth' => $dob,
                    'gender' => $row['gender'],
                    'marital_status' => $row['marital_status'],
                    'address' => $row['address'],
                    'emergency_contact_name' => $row['emergency_contact_name'],
                    'emergency_contact_phone' => $row['emergency_contact_phone'],
                ]);

                $result = $controller->store($request);

                DB::commit();
                $this->successCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->errorMessages[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\StudentSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:80',
        ]);

        /** @var User $user */
        $user = $request->user();
        $raw = trim($request->string('q'));
        $like = '%'.addcslashes($raw, '%_\\').'%';
        $groups = [];
        $students = $this->canSearch($user, 'students') ? $this->students($user, $raw, $like) : [];
        $staff = $this->canSearch($user, 'staff') ? $this->staff($like) : [];
        $invoices = $this->canSearch($user, 'finance') ? $this->invoices($like) : [];
        $payments = $this->canSearch($user, 'finance') ? $this->payments($like) : [];
        $vehicles = $this->canSearch($user, 'vehicles') ? $this->vehicles($like) : [];
        $trips = $this->canSearch($user, 'vehicles') ? $this->trips($like) : [];

        if ($students) {
            $groups[] = ['label' => 'STUDENTS', 'results' => $students];
        }
        if ($invoices || $payments) {
            $groups[] = ['label' => 'FINANCE', 'results' => array_merge($invoices, $payments)];
        }
        if ($staff) {
            $groups[] = ['label' => 'STAFF', 'results' => $staff];
        }
        if ($vehicles || $trips) {
            $groups[] = ['label' => 'TRANSPORT', 'results' => array_merge($vehicles, $trips)];
        }

        $flat = [];
        foreach ($groups as $group) {
            $flat = array_merge($flat, $group['results']);
        }

        return response()->json([
            'groups' => $groups,
            'results' => array_slice($flat, 0, 16),
        ]);
    }

    private function canSearch(User $user, string $module): bool
    {
        return match ($module) {
            'students' => $user->can('students.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Teacher', 'Senior Teacher', 'Deputy Senior Teacher', 'Academic Administrator', 'Accountant', 'Finance Officer', 'Director', 'Driver']),
            'staff' => $user->can('people.view') || $user->can('staff.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            'finance' => $user->can('finance.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Accountant', 'Finance Officer', 'Secretary', 'Director']),
            'vehicles' => $user->can('operations.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Driver']),
            default => false,
        };
    }

    private function students(User $user, string $raw, string $like): array
    {
        $q = Student::query();
        app(StudentSearchService::class)->applySearch($q, $raw);

        if (method_exists($user, 'hasTeacherLikeRole') && $user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($q);
        }

        return $q->with(['classroom', 'stream'])
            ->orderBy('first_name')
            ->limit(5)
            ->get()
            ->map(fn (Student $s) => [
                'title' => $s->full_name,
                'subtitle' => trim(($s->admission_number ?? '').' · '.trim(($s->classroom?->name ?? '').' '.($s->stream?->name ?? ''))),
                'module' => 'Student',
                'url' => $this->studentResultUrl($user, $s),
            ])
            ->all();
    }

    private function studentResultUrl(User $user, Student $student): string
    {
        if ($user->hasAnyRole(['Accountant', 'Finance Officer']) && Route::has('finance.student-statements.show')) {
            return route('finance.student-statements.show', $student->id);
        }

        if ($user->hasRole('Driver') && Route::has('transport.student-assignments.index')) {
            return route('transport.student-assignments.index', ['student_id' => $student->id]);
        }

        return route('students.show', $student->id);
    }

    private function staff(string $like): array
    {
        if (! Route::has('staff.show')) {
            return [];
        }

        return Staff::query()
            ->with('position')
            ->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('staff_id', 'like', $like)
                    ->orWhere('work_email', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
            })
            ->orderBy('first_name')
            ->limit(4)
            ->get()
            ->map(fn (Staff $s) => [
                'title' => trim($s->first_name.' '.$s->last_name),
                'subtitle' => trim(($s->staff_id ?? '').' · '.($s->position?->name ?? 'Staff')),
                'module' => 'Staff',
                'url' => route('staff.show', $s->id),
            ])
            ->all();
    }

    private function invoices(string $like): array
    {
        if (! Route::has('finance.invoices.show')) {
            return [];
        }

        return Invoice::query()
            ->with('student')
            ->whereNull('reversed_at')
            ->where(function ($q) use ($like) {
                $q->where('invoice_number', 'like', $like)
                    ->orWhereHas('student', function ($s) use ($like) {
                        $s->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('admission_number', 'like', $like);
                    });
            })
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Invoice $inv) => [
                'title' => $inv->invoice_number ?: 'Invoice #'.$inv->id,
                'subtitle' => trim(($inv->student?->full_name ?? '').' · Balance: '.(function_exists('format_money') ? format_money((float) $inv->balance) : number_format((float) $inv->balance, 2))),
                'module' => 'Invoice',
                'url' => route('finance.invoices.show', $inv->id),
            ])
            ->all();
    }

    private function payments(string $like): array
    {
        if (! Route::has('finance.payments.show')) {
            return [];
        }

        return Payment::query()
            ->with('student')
            ->where('reversed', false)
            ->where(function ($q) use ($like) {
                $q->where('receipt_number', 'like', $like)
                    ->orWhere('transaction_code', 'like', $like)
                    ->orWhere('mpesa_receipt_number', 'like', $like);
            })
            ->latest('payment_date')
            ->limit(4)
            ->get()
            ->map(fn (Payment $p) => [
                'title' => $p->receipt_number ?: $p->transaction_code ?: 'Payment #'.$p->id,
                'subtitle' => trim(($p->student?->full_name ?? '').' · '.(function_exists('format_money') ? format_money((float) $p->amount) : number_format((float) $p->amount, 2))),
                'module' => 'Payment',
                'url' => route('finance.payments.show', $p->id),
            ])
            ->all();
    }

    private function vehicles(string $like): array
    {
        if (! class_exists(Vehicle::class) || ! Route::has('transport.vehicles.index')) {
            return [];
        }

        return Vehicle::query()
            ->where(function ($q) use ($like) {
                $q->where('vehicle_number', 'like', $like)
                    ->orWhere('make', 'like', $like)
                    ->orWhere('model', 'like', $like);
            })
            ->limit(3)
            ->get()
            ->map(fn (Vehicle $v) => [
                'title' => $v->vehicle_number,
                'subtitle' => trim(($v->make ?? '').' '.($v->model ?? '')),
                'module' => 'Vehicle',
                'url' => Route::has('transport.vehicles.edit')
                    ? route('transport.vehicles.edit', $v->id)
                    : route('transport.vehicles.index'),
            ])
            ->all();
    }

    private function trips(string $like): array
    {
        if (! class_exists(\App\Models\Trip::class) || ! Route::has('transport.trips.index')) {
            return [];
        }

        return \App\Models\Trip::query()
            ->where('trip_name', 'like', $like)
            ->limit(3)
            ->get()
            ->map(fn ($trip) => [
                'title' => $trip->trip_name ?: 'Trip #'.$trip->id,
                'subtitle' => $trip->type ?: ($trip->direction ?: 'Trip'),
                'module' => 'Trip',
                'url' => Route::has('transport.trips.edit')
                    ? route('transport.trips.edit', $trip->id)
                    : route('transport.trips.index'),
            ])
            ->all();
    }
}

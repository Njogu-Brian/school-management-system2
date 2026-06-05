<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Student;
use App\Models\Trip;
use Illuminate\Http\Request;

class ApiOperationsSummaryController extends Controller
{
    public function show(Request $request)
    {
        $activeTrips = Trip::query()->count();
        $studentsOnTransport = Student::query()
            ->where('archive', false)
            ->whereNotNull('trip_id')
            ->count();
        $libraryBooks = Book::query()->count();
        $libraryAvailable = Book::query()->sum('available_copies');

        return response()->json([
            'success' => true,
            'data' => [
                'transport' => [
                    'active_trips' => $activeTrips,
                    'students_assigned' => $studentsOnTransport,
                ],
                'library' => [
                    'total_books' => $libraryBooks,
                    'available_books' => $libraryAvailable,
                ],
                'inventory' => [
                    'tracked_items' => 0,
                    'low_stock_items' => 0,
                ],
                'facilities' => [
                    'open_tickets' => 0,
                ],
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}

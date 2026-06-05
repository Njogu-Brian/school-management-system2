<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\FixedAsset;
use App\Models\InventoryItem;
use App\Models\Reports\OperationsFacility;
use App\Models\Student;
use App\Models\Trip;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
        $trackedItems = InventoryItem::active()->count();
        $lowStockItems = InventoryItem::active()->whereRaw('quantity <= min_stock_level')->count();
        $openFacilityTickets = OperationsFacility::query()->where('resolved', false)->count();
        $visitorsOnSite = Schema::hasTable('visitor_logs')
            ? VisitorLog::query()->whereNull('checked_out_at')->count()
            : 0;
        $activeAssets = Schema::hasTable('fixed_assets')
            ? FixedAsset::query()->where('status', 'active')->count()
            : 0;

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
                    'tracked_items' => $trackedItems,
                    'low_stock_items' => $lowStockItems,
                ],
                'facilities' => [
                    'open_tickets' => $openFacilityTickets,
                ],
                'visitors' => [
                    'on_site' => $visitorsOnSite,
                ],
                'assets' => [
                    'active' => $activeAssets,
                ],
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}

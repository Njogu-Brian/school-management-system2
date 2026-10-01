<?php

namespace Database\Seeders;

use App\Models\Academics\Subject;
use App\Models\Academics\Timetable;
use App\Models\Book;
use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\LibraryCard;
use App\Models\Pos\Order;
use App\Models\Pos\OrderItem;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Trip;
use App\Models\TripRun;
use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fill empty showcase modules on an already-populated (sanitized) demo database.
 * Skips tables that already have rows.
 */
class DemoGapFillSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLibrary();
        $this->seedHostel();
        $this->seedPos();
        $this->seedTimetable();
        $this->seedTripRuns();
    }

    private function seedLibrary(): void
    {
        if (! Schema::hasTable('books') || DB::table('books')->exists()) {
            $this->command?->line('Library: skipped (already has data or missing table)');

            return;
        }

        $student = Student::withoutGlobalScopes()->where('archive', 0)->orderBy('id')->first()
            ?? Student::withoutGlobalScopes()->orderBy('id')->first();
        if (! $student) {
            return;
        }

        $book = Book::create([
            'isbn' => '9789966000001',
            'title' => 'CBC Mathematics Practice',
            'author' => 'Demo Author',
            'publisher' => 'EduLynk Press',
            'publication_year' => 2025,
            'category' => 'Mathematics',
            'language' => 'English',
            'total_copies' => 5,
            'available_copies' => 4,
            'location' => 'Shelf A1',
            'description' => 'Demo library title for showcase',
        ]);

        Book::create([
            'isbn' => '9789966000002',
            'title' => 'Kiswahili Fasihi',
            'author' => 'Demo Mwandishi',
            'publisher' => 'EduLynk Press',
            'publication_year' => 2024,
            'category' => 'Literature',
            'language' => 'Kiswahili',
            'total_copies' => 3,
            'available_copies' => 3,
            'location' => 'Shelf B2',
        ]);

        $copy = BookCopy::create([
            'book_id' => $book->id,
            'copy_number' => 1,
            'barcode' => 'LIB0001',
            'status' => 'borrowed',
            'condition' => 'good',
        ]);
        BookCopy::create([
            'book_id' => $book->id,
            'copy_number' => 2,
            'barcode' => 'LIB0002',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $card = LibraryCard::create([
            'student_id' => $student->id,
            'card_number' => 'LC-DEMO-001',
            'issued_date' => Carbon::now()->subMonth(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => 'active',
            'max_borrow_limit' => 3,
            'current_borrow_count' => 1,
        ]);

        BookBorrowing::create([
            'book_copy_id' => $copy->id,
            'student_id' => $student->id,
            'library_card_id' => $card->id,
            'borrowed_date' => Carbon::now()->subDays(3),
            'due_date' => Carbon::now()->addDays(11),
            'status' => 'borrowed',
            'fine_amount' => 0,
            'fine_paid' => false,
        ]);

        $this->command?->info('Library: seeded books, copies, card, borrowing');
    }

    private function seedHostel(): void
    {
        if (! Schema::hasTable('hostels') || DB::table('hostels')->exists()) {
            $this->command?->line('Hostel: skipped');

            return;
        }

        $warden = Staff::query()->orderBy('id')->first();
        $student = Student::withoutGlobalScopes()->where('archive', 0)->orderBy('id')->skip(1)->first()
            ?? Student::withoutGlobalScopes()->orderBy('id')->first();

        $hostel = Hostel::create([
            'name' => 'Kilimanjaro Hostel',
            'type' => 'mixed',
            'capacity' => 60,
            'current_occupancy' => 1,
            'warden_id' => $warden?->id,
            'location' => 'Upper Campus',
            'description' => 'Demo boarding hostel',
            'is_active' => true,
        ]);

        $room = HostelRoom::create([
            'hostel_id' => $hostel->id,
            'room_number' => 'A1',
            'room_type' => 'dormitory',
            'capacity' => 6,
            'current_occupancy' => 1,
            'floor' => 1,
            'status' => 'available',
        ]);

        if ($student) {
            HostelAllocation::create([
                'student_id' => $student->id,
                'hostel_id' => $hostel->id,
                'room_id' => $room->id,
                'bed_number' => 'B1',
                'allocation_date' => Carbon::now()->subWeeks(2),
                'status' => 'active',
                'allocated_by' => $warden?->user_id,
            ]);
        }

        $this->command?->info('Hostel: seeded hostel, room, allocation');
    }

    private function seedPos(): void
    {
        if (! Schema::hasTable('pos_products') || DB::table('pos_products')->exists()) {
            $this->command?->line('POS: skipped');

            return;
        }

        $student = Student::withoutGlobalScopes()->where('archive', 0)->orderBy('id')->first();
        if (! $student) {
            return;
        }

        $product = Product::create([
            'name' => 'PE Kit - Demo',
            'sku' => 'PE-KIT-DEM',
            'barcode' => 'PEKIT001',
            'type' => 'uniform',
            'description' => 'Demo PE kit for showcase',
            'category' => 'Uniform',
            'brand' => 'EduLynk',
            'base_price' => 1800,
            'cost_price' => 1200,
            'stock_quantity' => 40,
            'min_stock_level' => 10,
            'track_stock' => true,
            'allow_backorders' => false,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'value' => 'M',
            'variant_type' => 'size',
            'price_adjustment' => 0,
            'stock_quantity' => 15,
            'sku' => 'PE-M',
            'barcode' => 'PEM001',
            'is_default' => true,
            'is_active' => true,
        ]);

        $order = Order::create([
            'student_id' => $student->id,
            'order_type' => 'uniform',
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 1800,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1800,
            'paid_amount' => 1800,
            'balance' => 0,
            'payment_method' => 'MPESA',
            'paid_at' => Carbon::now()->subDay(),
            'completed_at' => Carbon::now()->subDay(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'quantity' => 1,
            'unit_price' => 1800,
            'discount_amount' => 0,
            'total_price' => 1800,
            'fulfillment_status' => 'fulfilled',
            'quantity_fulfilled' => 1,
        ]);

        $this->command?->info('POS: seeded product, variant, order');
    }

    private function seedTimetable(): void
    {
        if (! Schema::hasTable('timetables') || DB::table('timetables')->exists()) {
            $this->command?->line('Timetable: skipped');

            return;
        }

        $year = AcademicYear::query()->where('is_active', true)->first() ?? AcademicYear::query()->orderByDesc('id')->first();
        $term = $year
            ? (Term::query()->where('academic_year_id', $year->id)->where('is_current', true)->first()
                ?? Term::query()->where('academic_year_id', $year->id)->orderBy('id')->first())
            : null;
        $classroomId = DB::table('classrooms')->orderBy('id')->value('id');
        $subject = Subject::query()->orderBy('id')->first();
        $staff = Staff::query()->orderBy('id')->first();

        if (! $year || ! $term || ! $classroomId || ! $subject || ! $staff) {
            $this->command?->warn('Timetable: missing dependencies, skipped');

            return;
        }

        $slots = [
            ['day' => 'Monday', 'period' => 1, 'start' => '08:00:00', 'end' => '08:40:00'],
            ['day' => 'Monday', 'period' => 2, 'start' => '08:40:00', 'end' => '09:20:00'],
            ['day' => 'Tuesday', 'period' => 1, 'start' => '08:00:00', 'end' => '08:40:00'],
            ['day' => 'Wednesday', 'period' => 1, 'start' => '08:00:00', 'end' => '08:40:00'],
        ];

        foreach ($slots as $slot) {
            Timetable::create([
                'classroom_id' => $classroomId,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'day' => $slot['day'],
                'period' => $slot['period'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'subject_id' => $subject->id,
                'staff_id' => $staff->id,
                'room' => 'Room 1',
                'is_break' => false,
            ]);
        }

        $this->command?->info('Timetable: seeded sample slots');
    }

    private function seedTripRuns(): void
    {
        if (! Schema::hasTable('trip_runs') || DB::table('trip_runs')->exists()) {
            $this->command?->line('Trip runs: skipped');

            return;
        }

        $trip = Trip::query()->orderBy('id')->first();
        if (! $trip) {
            $this->command?->warn('Trip runs: no trips found, skipped');

            return;
        }

        $driver = Staff::query()->orderBy('id')->first();

        TripRun::create([
            'trip_id' => $trip->id,
            'run_date' => Carbon::today()->subDay(),
            'driver_id' => $driver?->id,
            'vehicle_id' => $trip->vehicle_id,
            'status' => 'completed',
            'started_at' => Carbon::today()->subDay()->setTime(6, 30),
            'ended_at' => Carbon::today()->subDay()->setTime(8, 0),
            'last_latitude' => -1.286389,
            'last_longitude' => 36.817223,
            'last_location_at' => Carbon::today()->subDay()->setTime(8, 0),
            'started_by' => $driver?->user_id,
        ]);

        TripRun::create([
            'trip_id' => $trip->id,
            'run_date' => Carbon::today(),
            'driver_id' => $driver?->id,
            'vehicle_id' => $trip->vehicle_id,
            'status' => 'scheduled',
            'started_by' => $driver?->user_id,
        ]);

        $this->command?->info('Trip runs: seeded sample runs');
    }
}

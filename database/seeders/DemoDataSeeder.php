<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Announcement;
use App\Models\Book;
use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\CommunicationTemplate;
use App\Models\Document;
use App\Models\DropOffPoint;
use App\Models\Family;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LibraryCard;
use App\Models\ParentInfo;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Route;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Pos\Order;
use App\Models\Pos\OrderItem;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\RequirementTemplate;
use App\Models\RequirementType;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\StudentCategory;
use App\Models\StudentRequirement;
use App\Models\Term;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Votehead;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a fully-populated demo dataset with Kenyan context.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Note: Reference data seeders (roles, permissions, payment methods, etc.) 
            // are called by DatabaseSeeder before this seeder runs.
            // This seeder focuses on creating demo data (students, staff, invoices, etc.)

            // 2) Academic calendar
            $currentYear = (int) now()->format('Y');
            $academicYear = AcademicYear::firstOrCreate(
                ['year' => $currentYear],
                [
                    'name' => "{$currentYear}/" . ($currentYear + 1),
                    'start_date' => "{$currentYear}-01-01",
                    'end_date' => ($currentYear + 1) . "-12-31",
                    'is_active' => true,
                ]
            );

            $terms = collect([1, 2, 3])->map(function (int $termNumber) use ($academicYear) {
                return Term::firstOrCreate(
                    ['academic_year_id' => $academicYear->id, 'term_number' => $termNumber],
                    [
                        'name' => "Term {$termNumber}",
                        'start_date' => Carbon::parse("first day of January {$academicYear->year}")->addMonths(($termNumber - 1) * 4),
                        'end_date' => Carbon::parse("first day of January {$academicYear->year}")->addMonths($termNumber * 4)->subDay(),
                        'is_active' => $termNumber === 1,
                    ]
                );
            });

            // 3) Classes & streams
            $classrooms = collect([
                ['name' => 'Grade 4', 'description' => 'Upper Primary', 'capacity' => 45],
                ['name' => 'Grade 6', 'description' => 'Upper Primary', 'capacity' => 45],
                ['name' => 'Form 1', 'description' => 'Secondary', 'capacity' => 50],
            ])->map(function (array $data) {
                return \App\Models\Academics\Classroom::firstOrCreate(
                    ['name' => $data['name']],
                    [
                        'description' => $data['description'],
                        'capacity' => $data['capacity'],
                        'is_active' => true,
                    ]
                );
            });

            $streams = new Collection();
            foreach ($classrooms as $classroom) {
                foreach (['North', 'South'] as $streamName) {
                    $streams->push(
                        \App\Models\Academics\Stream::firstOrCreate(
                            ['name' => $streamName, 'classroom_id' => $classroom->id],
                            ['capacity' => 25, 'is_active' => true]
                        )
                    );
                }
            }

            // 4) Subjects (core CBC/8-4-4)
            $subjects = collect([
                ['code' => 'MAT', 'name' => 'Mathematics'],
                ['code' => 'ENG', 'name' => 'English'],
                ['code' => 'KIS', 'name' => 'Kiswahili'],
                ['code' => 'SCI', 'name' => 'Science'],
                ['code' => 'SST', 'name' => 'Social Studies'],
                ['code' => 'CRE', 'name' => 'Christian Religious Education'],
            ])->map(function (array $subject) {
                return \App\Models\Academics\Subject::updateOrCreate(
                    ['code' => $subject['code']],
                    [
                        'name' => $subject['name'],
                        'is_active' => true,
                        'is_optional' => false,
                        'level' => 'primary',
                    ]
                );
            });

            // 5) Staff & users (Kenyan names)
            $demoUsers = collect([
                ['name' => 'Admin Amina Mwangi', 'email' => 'admin.demo@school.test', 'role' => 'admin', 'phone' => '0712 000 111'],
                ['name' => 'Teacher David Otieno', 'email' => 'teacher.demo@school.test', 'role' => 'teacher', 'phone' => '0722 111 222'],
                ['name' => 'Bursar Mercy Njeri', 'email' => 'bursar.demo@school.test', 'role' => 'bursar', 'phone' => '0733 222 333'],
                ['name' => 'Accountant Jane Naliaka', 'email' => 'accountant.demo@school.test', 'role' => 'accountant', 'phone' => '0700 123 987'],
                ['name' => 'Parent Lydia Wangari', 'email' => 'parent.demo@school.test', 'role' => 'parent', 'phone' => '0719 654 321'],
                ['name' => 'Student Amani Mwangi', 'email' => 'student.demo@school.test', 'role' => 'student', 'phone' => '0799 111 555'],
            ])->map(function (array $userData) {
                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    ['name' => $userData['name'], 'password' => Hash::make('Demo@123'), 'must_change_password' => false]
                );

                // Ensure role exists then assign for meaningful permissions
                if (class_exists(Role::class)) {
                    $role = Role::findOrCreate($userData['role']);
                    $user->syncRoles([$role->name]);
                }

                return $user;
            });

            $staffMembers = $demoUsers->map(function (User $user, int $index) {
                $names = explode(' ', $user->name, 3);
                return Staff::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'first_name' => $names[0] ?? 'Staff',
                        'middle_name' => $names[1] ?? null,
                        'last_name' => $names[2] ?? 'Demo',
                        'phone_number' => '07' . rand(10, 99) . rand(100000, 999999),
                        'work_email' => $user->email,
                        'status' => 'active',
                        'employment_status' => 'active',
                        'employment_type' => 'full_time',
                        'hire_date' => Carbon::now()->subYears(rand(1, 5)),
                    ]
                );
            });

            // 6) Families & parents
            $families = collect([
                ['family' => 'Mwangi', 'father' => 'Peter Mwangi', 'mother' => 'Grace Wambui', 'phone' => '0701 345 001'],
                ['family' => 'Omondi', 'father' => 'James Omondi', 'mother' => 'Beatrice Achieng', 'phone' => '0715 892 114'],
                ['family' => 'Mutiso', 'father' => 'Anthony Mutiso', 'mother' => 'Catherine Ndunge', 'phone' => '0728 443 220'],
                ['family' => 'Njoroge', 'father' => 'Samuel Njoroge', 'mother' => 'Lydia Wangari', 'phone' => '0733 667 981'],
                ['family' => 'Abdi', 'father' => 'Hassan Abdi', 'mother' => 'Fatuma Noor', 'phone' => '0740 129 554'],
            ])->map(function (array $family) {
                $parent = ParentInfo::create([
                    'father_name' => $family['father'],
                    'father_phone' => $family['phone'],
                    'mother_name' => $family['mother'],
                    'mother_phone' => $family['phone'],
                ]);

                $familyModel = Family::create([
                    'guardian_name' => $family['father'],
                    'father_name' => $family['father'],
                    'mother_name' => $family['mother'],
                    'phone' => $family['phone'],
                    'father_phone' => $family['phone'],
                    'mother_phone' => $family['phone'],
                    'email' => strtolower($family['family']) . '@demo.test',
                ]);

                return ['family' => $familyModel, 'parent' => $parent];
            });

            // 7) Student categories for clarity
            $category = StudentCategory::firstOrCreate(['name' => 'Day Scholar'], ['description' => 'Demo day scholars']);

            // 8) Students with Kenyan names
            $studentNames = [
                ['first' => 'Amani', 'last' => 'Mwangi', 'gender' => 'Male'],
                ['first' => 'Zuri', 'last' => 'Omondi', 'gender' => 'Female'],
                ['first' => 'Baraka', 'last' => 'Mutiso', 'gender' => 'Male'],
                ['first' => 'Neema', 'last' => 'Njoroge', 'gender' => 'Female'],
                ['first' => 'Taji', 'last' => 'Abdi', 'gender' => 'Male'],
                ['first' => 'Malaika', 'last' => 'Mwangi', 'gender' => 'Female'],
                ['first' => 'Jabali', 'last' => 'Omondi', 'gender' => 'Male'],
                ['first' => 'Imani', 'last' => 'Mutiso', 'gender' => 'Female'],
                ['first' => 'Wanjiku', 'last' => 'Njoroge', 'gender' => 'Female'],
                ['first' => 'Kamau', 'last' => 'Abdi', 'gender' => 'Male'],
            ];

            $students = collect($studentNames)->map(function (array $name, int $index) use ($families, $classrooms, $streams, $category, $academicYear) {
                $familyIndex = $index % $families->count();
                $classroom = $classrooms[$index % $classrooms->count()];
                $stream = $streams->where('classroom_id', $classroom->id)->values()->get($index % 2);
                $family = $families[$familyIndex];

                return Student::create([
                    'admission_number' => 'ADM-' . str_pad((string) ($index + 101), 4, '0', STR_PAD_LEFT),
                    'first_name' => $name['first'],
                    'last_name' => $name['last'],
                    'gender' => $name['gender'],
                    'classroom_id' => $classroom->id,
                    'stream_id' => $stream->id ?? null,
                    'family_id' => $family['family']->id,
                    'parent_id' => $family['parent']->id,
                    'category_id' => $category->id,
                    'status' => 'active',
                    'admission_date' => Carbon::parse("{$academicYear->year}-01-15")->subYears(rand(0, 2)),
                    'home_county' => 'Nairobi',
                ]);
            });

            // 9) Attendance samples
            foreach ($students->take(5) as $student) {
                Attendance::create([
                    'student_id' => $student->id,
                    'date' => Carbon::now()->subDay(),
                    'status' => Attendance::STATUS_PRESENT,
                    'marked_at' => Carbon::now()->subDay()->setTime(8, 0),
                ]);
            }

            // 10) Finance: voteheads, fee structures, invoices & payments
            $voteheads = collect([
                ['code' => 'TUIT', 'name' => 'Tuition', 'amount' => 15000],
                ['code' => 'TRAN', 'name' => 'Transport', 'amount' => 4000],
                ['code' => 'HOS', 'name' => 'Hostel', 'amount' => 12000],
                ['code' => 'LIB', 'name' => 'Library', 'amount' => 1500],
                ['code' => 'ACT', 'name' => 'Activities', 'amount' => 2500],
            ])->map(function (array $vh) {
                return Votehead::updateOrCreate(
                    ['code' => $vh['code']],
                    [
                        'name' => $vh['name'],
                        'description' => $vh['name'] . ' fee',
                        'category' => 'Tuition',
                        'is_mandatory' => true,
                        'charge_type' => 'per_student',
                        'default_amount' => $vh['amount'],
                        'is_active' => true,
                    ]
                );
            });

            $feeStructure = FeeStructure::firstOrCreate(
                [
                    'name' => 'Demo Fee Structure ' . $academicYear->year,
                    'classroom_id' => $classrooms->first()->id,
                    'academic_year_id' => $academicYear->id,
                    'term_id' => $terms->first()->id,
                ],
                [
                    'is_active' => true,
                    'version' => 1,
                ]
            );

            foreach ($voteheads as $votehead) {
                FeeCharge::firstOrCreate(
                    ['fee_structure_id' => $feeStructure->id, 'votehead_id' => $votehead->id],
                    ['term' => 1, 'amount' => $votehead->default_amount ?? 1000]
                );
            }

            $paymentMethod = PaymentMethod::where('code', 'MPESA')->first() ?? PaymentMethod::first();

            $students->each(function (Student $student, int $index) use ($feeStructure, $voteheads, $academicYear, $terms, $paymentMethod) {
                $invoice = Invoice::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $terms->first()->id,
                    ],
                    [
                        'year' => $academicYear->year,
                        'term' => 1,
                        'invoice_number' => 'INV-' . str_pad((string) ($student->id + 500), 5, '0', STR_PAD_LEFT),
                        'total' => 0,
                        'paid_amount' => 0,
                        'balance' => 0,
                        'status' => 'unpaid',
                        'issued_date' => Carbon::now()->subWeek(),
                    ]
                );

                $total = 0;
                foreach ($voteheads as $votehead) {
                    $amount = $votehead->default_amount ?? 1000;
                    InvoiceItem::firstOrCreate(
                        ['invoice_id' => $invoice->id, 'votehead_id' => $votehead->id],
                        ['amount' => $amount, 'discount_amount' => 0, 'status' => 'active', 'effective_date' => Carbon::now()->subWeek(), 'source' => 'structure']
                    );
                    $total += $amount;
                }

                $invoice->update(['total' => $total, 'balance' => $total]);

                // Record partial payment for first few students
                if ($index < 4 && $paymentMethod) {
                    $paymentAmount = $total * 0.4;
                    $payment = Payment::create([
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $paymentAmount,
                        'allocated_amount' => $paymentAmount,
                        'unallocated_amount' => 0,
                        'payment_method_id' => $paymentMethod->id,
                        'payer_name' => $student->family?->guardian_name ?? $student->first_name . ' Parent',
                        'payer_type' => 'parent',
                        'payment_date' => Carbon::now()->subDays(2),
                        'narration' => 'Demo M-Pesa payment',
                    ]);

                    $invoice->update([
                        'paid_amount' => $paymentAmount,
                        'balance' => max(0, $total - $paymentAmount),
                        'status' => 'partially_paid',
                    ]);
                }
            });

            // 11) Transport: routes, trips, assignments
            $route = Route::factory()->create(['name' => 'Nairobi Westlands Loop']);
            $vehicle = Vehicle::factory()->create(['registration_number' => 'KDA 234A']);
            $dropPoints = collect(['Westlands Stage', 'Lavington Mall', 'Kilimani Junction'])
                ->map(fn ($name) => DropOffPoint::firstOrCreate(['name' => $name, 'route_id' => $route->id]));

            $trip = Trip::firstOrCreate(
                ['name' => 'Morning Pickup', 'route_id' => $route->id],
                ['type' => 'morning', 'vehicle_id' => $vehicle->id]
            );

            $students->take(5)->each(function (Student $student, int $index) use ($trip, $dropPoints) {
                StudentAssignment::firstOrCreate(
                    ['student_id' => $student->id],
                    [
                        'morning_trip_id' => $trip->id,
                        'evening_trip_id' => $trip->id,
                        'morning_drop_off_point_id' => $dropPoints[$index % $dropPoints->count()]->id,
                        'evening_drop_off_point_id' => $dropPoints[$index % $dropPoints->count()]->id,
                    ]
                );
            });

            // 12) Library: books, copies, borrowing
            $book = Book::firstOrCreate(
                ['isbn' => '9789966845081'],
                [
                    'title' => 'Fasihi ya Kiswahili',
                    'author' => 'Ngugi wa Thiong\'o',
                    'publisher' => 'EA Publishers',
                    'publication_year' => 2022,
                    'category' => 'Literature',
                    'language' => 'Kiswahili',
                    'total_copies' => 5,
                    'available_copies' => 5,
                    'location' => 'Library Shelf A1',
                ]
            );

            $copies = collect(range(1, 3))->map(function (int $number) use ($book) {
                return BookCopy::firstOrCreate(
                    ['book_id' => $book->id, 'copy_number' => $number],
                    ['barcode' => 'LIB' . str_pad((string) $number, 4, '0', STR_PAD_LEFT), 'status' => 'available', 'condition' => 'good']
                );
            });

            $libraryCard = LibraryCard::firstOrCreate(
                ['student_id' => $students->first()->id],
                [
                    'card_number' => LibraryCard::generateCardNumber(),
                    'issued_date' => Carbon::now()->subMonth(),
                    'expiry_date' => Carbon::now()->addYear(),
                    'status' => 'active',
                    'max_borrow_limit' => 3,
                    'current_borrow_count' => 0,
                ]
            );

            BookBorrowing::firstOrCreate(
                ['book_copy_id' => $copies->first()->id, 'student_id' => $students->first()->id],
                [
                    'library_card_id' => $libraryCard->id,
                    'borrowed_date' => Carbon::now()->subDays(5),
                    'due_date' => Carbon::now()->addDays(5),
                    'status' => 'borrowed',
                    'fine_amount' => 0,
                    'fine_paid' => false,
                ]
            );

            $book->update(['available_copies' => max(0, $book->available_copies - 1)]);

            // 13) Hostel: hostel, room, allocation
            $warden = $staffMembers->first();
            $hostel = Hostel::firstOrCreate(
                ['name' => 'Kilimanjaro Hostel'],
                [
                    'type' => 'boarding',
                    'capacity' => 60,
                    'current_occupancy' => 10,
                    'warden_id' => $warden->id ?? null,
                    'location' => 'Upper Hill',
                    'description' => 'Mixed boarding demo hostel',
                    'is_active' => true,
                ]
            );

            $room = HostelRoom::firstOrCreate(
                ['hostel_id' => $hostel->id, 'room_number' => 'A1'],
                [
                    'room_type' => 'standard',
                    'capacity' => 6,
                    'current_occupancy' => 2,
                    'floor' => 1,
                    'status' => 'available',
                ]
            );

            HostelAllocation::firstOrCreate(
                ['student_id' => $students->get(1)?->id ?? $students->first()->id],
                [
                    'hostel_id' => $hostel->id,
                    'room_id' => $room->id,
                    'bed_number' => 'B2',
                    'allocation_date' => Carbon::now()->subWeeks(2),
                    'status' => 'active',
                    'allocated_by' => $warden?->id,
                ]
            );

            // 14) Inventory, requirements, and POS
            $requirementTypes = collect([
                ['name' => 'Uniform', 'category' => 'Student Items'],
                ['name' => 'Stationery', 'category' => 'Student Items'],
            ])->map(fn ($rt) => RequirementType::firstOrCreate(
                ['name' => $rt['name']],
                ['category' => $rt['category'], 'description' => $rt['name'] . ' items', 'is_active' => true]
            ));

            $inventoryItems = collect([
                ['name' => 'Exercise Books (Math)', 'category' => 'Stationery', 'unit' => 'pcs', 'quantity' => 200, 'min_stock_level' => 50, 'unit_cost' => 30],
                ['name' => 'PE Kit - Green', 'category' => 'Uniform', 'unit' => 'sets', 'quantity' => 40, 'min_stock_level' => 10, 'unit_cost' => 1200],
                ['name' => 'Lab Safety Goggles', 'category' => 'Science', 'unit' => 'pcs', 'quantity' => 25, 'min_stock_level' => 5, 'unit_cost' => 600],
            ])->map(function (array $item) use ($demoUsers) {
                $model = InventoryItem::firstOrCreate(
                    ['name' => $item['name']],
                    [
                        'category' => $item['category'],
                        'brand' => 'DemoBrand',
                        'description' => $item['name'] . ' demo stock',
                        'unit' => $item['unit'],
                        'quantity' => 0,
                        'min_stock_level' => $item['min_stock_level'],
                        'unit_cost' => $item['unit_cost'],
                        'location' => 'Store A',
                        'is_active' => true,
                    ]
                );

                InventoryTransaction::create([
                    'inventory_item_id' => $model->id,
                    'user_id' => $demoUsers->first()->id ?? null,
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'notes' => 'Initial demo stock load',
                    'reference_number' => 'GRN-' . strtoupper(uniqid()),
                ]);

                return $model;
            });

            $uniformTemplate = RequirementTemplate::firstOrCreate(
                [
                    'requirement_type_id' => $requirementTypes->firstWhere('name', 'Uniform')?->id,
                    'classroom_id' => $classrooms->first()->id,
                    'academic_year_id' => $academicYear->id,
                    'term_id' => $terms->first()->id,
                    'brand' => 'Demo Uniform',
                ],
                [
                    'quantity_per_student' => 1,
                    'unit' => 'set',
                    'student_type' => 'Day Scholar',
                    'leave_with_teacher' => false,
                    'is_verification_only' => false,
                    'is_active' => true,
                ]
            );

            $uniformProduct = Product::firstOrCreate(
                ['name' => 'PE Kit - Demo', 'sku' => 'PE-KIT-DEM'],
                [
                    'barcode' => 'PEKIT001',
                    'type' => 'stock',
                    'inventory_item_id' => $inventoryItems->firstWhere('name', 'PE Kit - Green')?->id,
                    'requirement_type_id' => $requirementTypes->firstWhere('name', 'Uniform')?->id,
                    'description' => 'PE kit for demo learners',
                    'category' => 'Uniform',
                    'brand' => 'DemoBrand',
                    'base_price' => 1800,
                    'cost_price' => 1200,
                    'stock_quantity' => 40,
                    'min_stock_level' => 10,
                    'track_stock' => true,
                    'allow_backorders' => false,
                    'is_active' => true,
                    'is_featured' => true,
                ]
            );

            $variantM = ProductVariant::firstOrCreate(
                ['product_id' => $uniformProduct->id, 'name' => 'Size M'],
                ['value' => 'M', 'variant_type' => 'size', 'price_adjustment' => 0, 'stock_quantity' => 15, 'sku' => 'PE-M', 'barcode' => 'PEM001', 'is_default' => true, 'is_active' => true]
            );

            $variantL = ProductVariant::firstOrCreate(
                ['product_id' => $uniformProduct->id, 'name' => 'Size L'],
                ['value' => 'L', 'variant_type' => 'size', 'price_adjustment' => 150, 'stock_quantity' => 12, 'sku' => 'PE-L', 'barcode' => 'PEL001', 'is_default' => false, 'is_active' => true]
            );

            // POS order for first student
            $posOrder = Order::firstOrCreate(
                [
                    'student_id' => $students->first()->id,
                    'order_type' => 'shop',
                ],
                [
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'balance' => 0,
                    'payment_method' => 'MPESA',
                    'paid_at' => Carbon::now()->subDay(),
                    'completed_at' => Carbon::now()->subDay(),
                ]
            );

            $orderItem = OrderItem::firstOrCreate(
                [
                    'order_id' => $posOrder->id,
                    'product_id' => $uniformProduct->id,
                    'variant_id' => $variantM->id,
                ],
                [
                    'product_name' => $uniformProduct->name,
                    'variant_name' => $variantM->name,
                    'quantity' => 1,
                    'unit_price' => $uniformProduct->getPriceForVariant($variantM->id),
                    'discount_amount' => 0,
                    'total_price' => $uniformProduct->getPriceForVariant($variantM->id),
                    'fulfillment_status' => 'fulfilled',
                    'quantity_fulfilled' => 1,
                    'requirement_template_id' => $uniformTemplate->id,
                ]
            );

            $posOrder->calculateTotals();
            $posOrder->markAsPaid('MPESA', 'MPESA-DEMO-'.rand(1000,9999));

            // Student requirement tied to POS order
            $studentRequirement = StudentRequirement::firstOrCreate(
                [
                    'student_id' => $students->first()->id,
                    'requirement_template_id' => $uniformTemplate->id,
                    'academic_year_id' => $academicYear->id,
                    'term_id' => $terms->first()->id,
                ],
                [
                    'collected_by' => $demoUsers->first()->id,
                    'quantity_required' => 1,
                    'quantity_collected' => 1,
                    'quantity_missing' => 0,
                    'status' => 'complete',
                    'collected_at' => Carbon::now()->subDay(),
                    'notified_parent' => true,
                    'purchased_through_pos' => true,
                    'pos_order_id' => $posOrder->id,
                    'pos_order_item_id' => $orderItem->id,
                ]
            );

            InventoryTransaction::create([
                'inventory_item_id' => $inventoryItems->firstWhere('name', 'PE Kit - Green')?->id,
                'user_id' => $demoUsers->first()->id ?? null,
                'student_requirement_id' => $studentRequirement->id,
                'type' => 'out',
                'quantity' => 1,
                'unit_cost' => 1200,
                'notes' => 'Issued PE kit to student (demo)',
                'reference_number' => 'ISSUE-' . strtoupper(uniqid()),
            ]);

            // Requisition for lab goggles
            $requisition = Requisition::firstOrCreate(
                ['purpose' => 'Lab safety gear for demo', 'type' => 'inventory'],
                [
                    'requested_by' => $demoUsers->first()->id,
                    'approved_by' => $demoUsers->first()->id,
                    'status' => 'approved',
                    'requested_at' => Carbon::now()->subDays(3),
                    'approved_at' => Carbon::now()->subDays(2),
                ]
            );

            $reqItem = RequisitionItem::firstOrCreate(
                ['requisition_id' => $requisition->id, 'inventory_item_id' => $inventoryItems->firstWhere('name', 'Lab Safety Goggles')?->id],
                [
                    'requirement_type_id' => $requirementTypes->firstWhere('name', 'Uniform')?->id,
                    'item_name' => 'Lab Safety Goggles',
                    'brand' => 'DemoBrand',
                    'quantity_requested' => 10,
                    'quantity_approved' => 8,
                    'quantity_issued' => 8,
                    'unit' => 'pcs',
                    'purpose' => 'Science lab safety',
                ]
            );

            InventoryTransaction::create([
                'inventory_item_id' => $reqItem->inventory_item_id,
                'user_id' => $demoUsers->first()->id ?? null,
                'requisition_id' => $requisition->id,
                'type' => 'out',
                'quantity' => 8,
                'unit_cost' => 600,
                'notes' => 'Issued lab goggles per requisition',
                'reference_number' => 'REQ-ISSUE-' . strtoupper(uniqid()),
            ]);

            // POS payment transaction linked to order (for UI visibility)
            PaymentTransaction::firstOrCreate(
                ['transaction_id' => 'TXPOS-' . $posOrder->id],
                [
                    'student_id' => $students->first()->id,
                    'invoice_id' => null,
                    'gateway' => 'mpesa',
                    'reference' => $posOrder->payment_reference ?? 'MPESA-DEMO',
                    'amount' => $posOrder->total_amount,
                    'currency' => 'KES',
                    'status' => 'completed',
                    'paid_at' => $posOrder->paid_at,
                    'gateway_response' => ['message' => 'Demo MPESA payment successful'],
                ]
            );

            // 15) Payroll
            $payrollPeriod = PayrollPeriod::firstOrCreate(
                ['year' => now()->year, 'month' => now()->month],
                [
                    'period_name' => now()->format('F Y'),
                    'start_date' => now()->startOfMonth(),
                    'end_date' => now()->endOfMonth(),
                    'pay_date' => now()->endOfMonth(),
                    'status' => 'draft',
                ]
            );

            $staffMembers->each(function (Staff $staff) use ($payrollPeriod, $demoUsers) {
                $structure = SalaryStructure::firstOrCreate(
                    ['staff_id' => $staff->id, 'is_active' => true],
                    [
                        'basic_salary' => 45000 + rand(0, 15000),
                        'housing_allowance' => 8000,
                        'transport_allowance' => 4000,
                        'medical_allowance' => 3000,
                        'other_allowances' => 2000,
                        'nssf_deduction' => 1080,
                        'nhif_deduction' => 1700,
                        'paye_deduction' => 6500,
                        'other_deductions' => 0,
                        'effective_from' => now()->subMonths(2),
                        'is_active' => true,
                        'created_by' => $demoUsers->first()->id ?? null,
                    ]
                );
                $structure->calculateTotals();
                $structure->save();

                $record = PayrollRecord::firstOrCreate(
                    [
                        'payroll_period_id' => $payrollPeriod->id,
                        'staff_id' => $staff->id,
                        'salary_structure_id' => $structure->id,
                    ],
                    [
                        'basic_salary' => $structure->basic_salary,
                        'housing_allowance' => $structure->housing_allowance,
                        'transport_allowance' => $structure->transport_allowance,
                        'medical_allowance' => $structure->medical_allowance,
                        'other_allowances' => $structure->other_allowances,
                        'nssf_deduction' => $structure->nssf_deduction,
                        'nhif_deduction' => $structure->nhif_deduction,
                        'paye_deduction' => $structure->paye_deduction,
                        'other_deductions' => 0,
                        'bonus' => 2500,
                        'advance_deduction' => 0,
                        'custom_deductions_total' => 0,
                        'status' => 'approved',
                        'days_worked' => 22,
                        'days_in_period' => 22,
                        'created_by' => $demoUsers->first()->id ?? null,
                    ]
                );

                $record->calculateTotals();
                $record->generatePayslipNumber();
                $record->save();
            });

            $payrollPeriod->refresh()->calculateTotals()->save();

            // 16) Documents for HR and students
            Document::firstOrCreate(
                ['title' => 'Student Report Sample', 'documentable_type' => Student::class, 'documentable_id' => $students->first()->id],
                [
                    'description' => 'Demo report card PDF',
                    'file_path' => 'documents/demo/report-sample.pdf',
                    'file_name' => 'report-sample.pdf',
                    'file_type' => 'application/pdf',
                    'file_size' => 102400,
                    'category' => 'Academics',
                    'document_type' => 'report_card',
                    'version' => 1,
                    'is_active' => true,
                    'uploaded_by' => $demoUsers->first()->id ?? null,
                ]
            );

            Document::firstOrCreate(
                ['title' => 'HR Contract Sample', 'documentable_type' => Staff::class, 'documentable_id' => $staffMembers->first()->id],
                [
                    'description' => 'Demo employment contract',
                    'file_path' => 'documents/demo/hr-contract.pdf',
                    'file_name' => 'hr-contract.pdf',
                    'file_type' => 'application/pdf',
                    'file_size' => 204800,
                    'category' => 'HR',
                    'document_type' => 'contract',
                    'version' => 1,
                    'is_active' => true,
                    'uploaded_by' => $demoUsers->first()->id ?? null,
                ]
            );

            // 14) Communication templates & announcements
            CommunicationTemplate::firstOrCreate(
                ['name' => 'Fee Reminder'],
                [
                    'channel' => 'sms',
                    'subject' => 'Fee Reminder',
                    'body' => 'Habari mzazi, tafadhali lipia ada yako iliyobaki. Asante.',
                    'placeholders' => ['student_name', 'balance'],
                ]
            );

            Announcement::firstOrCreate(
                ['title' => 'Welcome to Demo Mode'],
                [
                    'content' => 'Hii ni data ya majaribio ili uone moduli zote na taarifa halisi.',
                    'published_at' => Carbon::now()->subDay(),
                    'published_by' => $demoUsers->first()->id ?? null,
                ]
            );
        });
    }
}


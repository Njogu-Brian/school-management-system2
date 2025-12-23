<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceRecipient;
use App\Models\AttendanceReasonCode;
use App\Models\Family;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentAcademicHistory;
use App\Models\StudentMedicalRecord;
use App\Models\StudentExtracurricularActivity;
use App\Models\Staff;
use App\Models\User;
use App\Models\Academics\Behaviour;
use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportCardSkill;
use App\Models\Academics\Homework;
use App\Models\Academics\StudentDiary;
use App\Models\Academics\DiaryEntry;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\Votehead;
use App\Models\FeeStructure;
use App\Models\FeeCharge;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\FeeConcession;
use App\Models\PaymentAllocation;
use App\Models\Academics\ExamType;
use App\Models\Academics\Timetable;
use App\Models\CurriculumDesign;
use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\LessonPlan;
use App\Models\Academics\PortfolioAssessment;
use App\Models\Academics\CBCCoreCompetency;
use App\Models\Academics\CBCPerformanceLevel;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\Event;
use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Comprehensive2025Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 0) Core roles and admin user (match middleware names)
            if (class_exists(Role::class)) {
                $roles = ['Super Admin','Admin','Secretary','Teacher','teacher','Accountant','Parent'];
                foreach ($roles as $r) {
                    Role::findOrCreate($r);
                }

                $permissions = [
                    'dashboard.view',
                    'students.view','students.manage',
                    'staff.view','staff.manage',
                    'attendance.view','attendance.mark',
                    'academics.view','academics.manage',
                    'exams.view','exams.manage',
                    'report_cards.view','report_cards.manage',
                    'finance.view','finance.manage',
                    'transport.view','transport.manage',
                    'inventory.view','inventory.manage',
                    'events.manage','settings.manage',
                    'extra_curricular.view','extra_curricular.create','extra_curricular.edit','extra_curricular.delete',
                ];
                foreach ($permissions as $p) {
                    Permission::findOrCreate($p);
                }

                $rolePerms = [
                    'Super Admin' => $permissions,
                    'Admin' => $permissions,
                    'Secretary' => [
                        'dashboard.view','students.view','students.manage',
                        'attendance.view','attendance.mark',
                        'academics.view','academics.manage',
                        'exams.view','exams.manage',
                        'report_cards.view','report_cards.manage',
                        'events.manage',
                        'extra_curricular.view','extra_curricular.create','extra_curricular.edit',
                    ],
                    'Teacher' => [
                        'dashboard.view',
                        'students.view',
                        'attendance.view','attendance.mark',
                        'academics.view','academics.manage',
                        'exams.view','exams.manage',
                        'report_cards.view','report_cards.manage',
                        'extra_curricular.view','extra_curricular.create',
                    ],
                    'teacher' => [
                        'dashboard.view',
                        'students.view',
                        'attendance.view','attendance.mark',
                        'academics.view','academics.manage',
                        'exams.view','exams.manage',
                        'report_cards.view','report_cards.manage',
                        'extra_curricular.view','extra_curricular.create',
                    ],
                    'Accountant' => [
                        'dashboard.view',
                        'finance.view','finance.manage',
                        'students.view',
                    ],
                    'Parent' => [
                        'dashboard.view',
                        'students.view',
                        'attendance.view',
                        'report_cards.view',
                    ],
                ];
                foreach ($rolePerms as $roleName => $perms) {
                    $role = Role::findByName($roleName);
                    $role->syncPermissions($perms);
                }
            }
            $adminUser = User::updateOrCreate(
                ['email' => 'admin@demo.test'],
                ['name' => 'Super Admin', 'password' => Hash::make('Password@123'), 'must_change_password' => false]
            );
            if (class_exists(Role::class)) {
                $adminUser->syncRoles(['Super Admin']);
            }

            // 1) Academic Year + Kenyan 2025 term dates (MoE calendar approximation)
            $year = 2025;
            $academicYear = AcademicYear::updateOrCreate(
                ['year' => $year],
                ['is_active' => true]
            );

            $termDefinitions = [
                ['name' => 'Term 1', 'start' => "{$year}-01-06", 'end' => "{$year}-03-28"],
                ['name' => 'Term 2', 'start' => "{$year}-04-28", 'end' => "{$year}-08-01"],
                ['name' => 'Term 3', 'start' => "{$year}-08-26", 'end' => "{$year}-11-14"],
            ];

            $terms = collect($termDefinitions)->map(function ($t, $idx) use ($academicYear) {
                return \App\Models\Term::updateOrCreate(
                    ['academic_year_id' => $academicYear->id, 'name' => $t['name']],
                    [
                        'start_date' => Carbon::parse($t['start']),
                        'end_date' => Carbon::parse($t['end']),
                        'is_current' => $idx === 2, // focus on Term 3 data
                    ]
                );
            })->keyBy('name');

            // 2) CBC classrooms Grade 1–9 with North/South streams and next-class mapping
            $classrooms = collect(range(1, 9))->map(function (int $grade) {
                $levelType = match (true) {
                    $grade <= 3 => 'lower_primary',
                    $grade <= 6 => 'upper_primary',
                    default => 'junior_high',
                };

                return Classroom::updateOrCreate(
                    ['name' => "Grade {$grade}"],
                    ['level_type' => $levelType, 'is_beginner' => $grade === 1]
                );
            });

            // map next class ids
            $classrooms->each(function (Classroom $classroom, int $idx) use ($classrooms) {
                $next = $classrooms->get($idx + 1);
                if ($next && $classroom->next_class_id !== $next->id) {
                    $classroom->next_class_id = $next->id;
                    $classroom->save();
                }
            });

            $streams = collect();
            foreach ($classrooms as $classroom) {
                foreach (['North', 'South'] as $streamName) {
                    $streams->push(Stream::updateOrCreate(
                        ['name' => "{$classroom->name} {$streamName}", 'classroom_id' => $classroom->id]
                    ));
                }
            }

            // 3) Core CBC subjects
            $cbcSubjects = [
                ['code' => 'MAT', 'name' => 'Mathematics'],
                ['code' => 'ENG', 'name' => 'English'],
                ['code' => 'KIS', 'name' => 'Kiswahili'],
                ['code' => 'SCI', 'name' => 'Science & Technology'],
                ['code' => 'SST', 'name' => 'Social Studies'],
                ['code' => 'CRE', 'name' => 'Christian Religious Education'],
                ['code' => 'AGR', 'name' => 'Agriculture'],
                ['code' => 'PE',  'name' => 'Physical Education'],
                ['code' => 'ART', 'name' => 'Creative Arts'],
                ['code' => 'LIF', 'name' => 'Life Skills'],
                ['code' => 'CS',  'name' => 'Computer Studies'],
            ];
            $subjects = collect($cbcSubjects)->map(function ($s) {
                return Subject::updateOrCreate(
                    ['code' => $s['code']],
                    ['name' => $s['name'], 'is_active' => true, 'is_optional' => false, 'level' => 'cbc']
                );
            });

            // 4) Staff (teachers + operations)
            $staffDefinitions = [
                ['name' => 'Alice Wanjiru', 'role' => 'Teacher', 'job_title' => 'Mathematics Teacher'],
                ['name' => 'David Otieno', 'role' => 'Teacher', 'job_title' => 'Science Teacher'],
                ['name' => 'Mercy Njeri', 'role' => 'Teacher', 'job_title' => 'English Teacher'],
                ['name' => 'Peter Mwangi', 'role' => 'Teacher', 'job_title' => 'Social Studies Teacher'],
                ['name' => 'Grace Achieng', 'role' => 'Teacher', 'job_title' => 'ICT Teacher'],
                ['name' => 'John Kariuki', 'role' => 'Teacher', 'job_title' => 'PE Teacher'],
                ['name' => 'Ann Wambui', 'role' => 'Administrator', 'job_title' => 'School Admin'],
                ['name' => 'Samuel Kiptoo', 'role' => 'Accountant', 'job_title' => 'Accountant'],
                ['name' => 'Lucy Nduta', 'role' => 'Secretary', 'job_title' => 'Secretary'],
                ['name' => 'Brian Mumo', 'role' => 'Chef', 'job_title' => 'Chef'],
                ['name' => 'Kevin Mutua', 'role' => 'Driver', 'job_title' => 'Driver'],
                ['name' => 'Eunice Cherono', 'role' => 'Janitor', 'job_title' => 'Janitor'],
                ['name' => 'Paul Ochieng', 'role' => 'Security', 'job_title' => 'Security Officer'],
            ];

            $staffMembers = collect($staffDefinitions)->map(function ($def, int $idx) use ($year) {
                $email = Str::slug($def['name'], '.') . '@school.test';
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $def['name'],
                        'password' => Hash::make('Password@123'),
                        'must_change_password' => false,
                    ]
                );

                // ensure role
                if (class_exists(Role::class)) {
                    $role = Role::findOrCreate(strtolower($def['role']));
                    $user->syncRoles([$role->name]);
                }

                [$first, $last] = array_pad(explode(' ', $def['name'], 2), 2, 'Staff');

                // Ensure unique staff_id even if data already exists
                $staff = Staff::firstOrNew(['user_id' => $user->id]);
                if (!$staff->exists) {
                    $counter = $idx + 1;
                    do {
                        $candidate = 'STF-' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
                        $counter++;
                    } while (Staff::where('staff_id', $candidate)->exists());
                    $staff->staff_id = $candidate;
                }

                $staff->fill([
                    'first_name' => $first,
                    'last_name' => $last,
                    'work_email' => $email,
                    'personal_email' => 'personal+' . $email,
                    'phone_number' => '07' . rand(10, 99) . rand(100000, 999999),
                    'id_number' => (string) (31000000 + rand(1000, 9999)),
                    'gender' => 'other',
                    'marital_status' => 'married',
                    'residential_address' => 'Nairobi, Kenya',
                    'emergency_contact_name' => 'Next of Kin ' . $last,
                    'emergency_contact_relationship' => 'Spouse',
                    'emergency_contact_phone' => '07' . rand(10, 99) . rand(100000, 999999),
                    'kra_pin' => 'A' . rand(1000000, 9999999) . 'B',
                    'nssf' => 'NSSF' . rand(10000, 99999),
                    'nhif' => 'NHIF' . rand(10000, 99999),
                    'bank_name' => 'Equity Bank',
                    'bank_branch' => 'Westlands',
                    'bank_account' => '01' . rand(100000000, 999999999),
                    'department_id' => null,
                    'job_title_id' => null,
                    'staff_category_id' => null,
                    'supervisor_id' => null,
                    'photo' => null,
                    'status' => 'active',
                    'hire_date' => Carbon::parse("{$year}-01-02"),
                    'employment_status' => 'active',
                    'employment_type' => 'full_time',
                    'contract_start_date' => Carbon::parse("{$year}-01-02"),
                ]);

                $staff->save();
                return $staff;
            });

            // Teacher pool for classroom/stream assignments
            $teacherPool = $staffMembers->take(6);
            $classTeacherUserIds = [];
            foreach ($classrooms as $idx => $classroom) {
                $teacher = $teacherPool[$idx % $teacherPool->count()];
                $classTeacherUserIds[$classroom->id] = $teacher->user_id;
            }

            // Attendance recipients (one per main teacher/admin)
            $recipientTeachers = $teacherPool->take(3);
            foreach ($recipientTeachers as $idx => $staff) {
                AttendanceRecipient::updateOrCreate(
                    ['staff_id' => $staff->id],
                    [
                        'label' => 'Attendance Recipient ' . ($idx + 1),
                        'classroom_ids' => $classrooms->pluck('id')->toArray(),
                        'active' => true,
                    ]
                );
            }

            // Attendance reason codes
            $reasonCodes = [
                ['code' => 'SICK', 'name' => 'Sick Leave', 'description' => 'Sick leave'],
                ['code' => 'TRAVEL', 'name' => 'Travel', 'description' => 'Travel'],
                ['code' => 'FAMILY', 'name' => 'Family Emergency', 'description' => 'Family emergency'],
                ['code' => 'TRAFFIC', 'name' => 'Traffic Delay', 'description' => 'Traffic/transport delay'],
            ];
            foreach ($reasonCodes as $rc) {
                AttendanceReasonCode::updateOrCreate(
                    ['code' => $rc['code']],
                    ['name' => $rc['name'], 'description' => $rc['description']]
                );
            }

            // 5) Families and parents
            $familyDefs = [
                ['family' => 'Mwangi', 'father' => 'Peter Mwangi', 'mother' => 'Grace Wambui', 'phone' => '0701000100'],
                ['family' => 'Omondi', 'father' => 'James Omondi', 'mother' => 'Beatrice Achieng', 'phone' => '0702000200'],
                ['family' => 'Mutiso', 'father' => 'Anthony Mutiso', 'mother' => 'Catherine Ndunge', 'phone' => '0703000300'],
                ['family' => 'Kamau', 'father' => 'Joseph Kamau', 'mother' => 'Mercy Wanjiku', 'phone' => '0704000400'],
            ];
            $families = collect($familyDefs)->map(function ($family) {
                $parent = ParentInfo::updateOrCreate(
                    ['father_phone' => $family['phone']],
                    [
                        'father_name' => $family['father'],
                        'mother_name' => $family['mother'],
                        'guardian_name' => $family['father'],
                        'guardian_phone' => $family['phone'],
                        'guardian_relationship' => 'parent',
                        'family_income_bracket' => 'middle',
                        'primary_contact_person' => $family['father'],
                        'communication_preference' => 'sms',
                        'language_preference' => 'en',
                    ]
                );

                $familyModel = Family::updateOrCreate(
                    ['email' => strtolower($family['family']) . '@family.test'],
                    [
                        'guardian_name' => $family['father'],
                        'father_name' => $family['father'],
                        'mother_name' => $family['mother'],
                        'phone' => $family['phone'],
                        'father_phone' => $family['phone'],
                        'mother_phone' => $family['phone'],
                        'father_email' => strtolower($family['father']) . '@family.test',
                        'mother_email' => strtolower($family['mother']) . '@family.test',
                    ]
                );

                return ['family' => $familyModel, 'parent' => $parent];
            });

            // 6) Students spread across classes with detailed fields
            $category = StudentCategory::firstOrCreate(['name' => 'Day Scholar'], ['description' => 'CBC day scholars']);
            $studentDefinitions = [];

            $givenNames = ['Amani', 'Zuri', 'Baraka', 'Neema', 'Taji', 'Malaika', 'Jabali', 'Imani', 'Wanjiku', 'Kamau', 'Otieno', 'Chebet', 'Cherono', 'Nduta', 'Naliaka', 'Wekesa', 'Mutheu', 'Mburu'];
            $surnames = ['Mwangi', 'Omondi', 'Mutiso', 'Njoroge', 'Abdi', 'Kiptoo', 'Kamau', 'Were', 'Owuor'];

            foreach (range(0, 23) as $i) {
                $studentDefinitions[] = [
                    'first' => $givenNames[$i % count($givenNames)],
                    'last' => $surnames[$i % count($surnames)],
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'dob' => Carbon::parse("2012-01-01")->addYears(($i % 9)), // younger grades younger age
                ];
            }

            $students = collect($studentDefinitions)->map(function ($def, int $idx) use ($classrooms, $streams, $families, $category, $academicYear) {
                $classroom = $classrooms[$idx % $classrooms->count()];
                $stream = $streams->where('classroom_id', $classroom->id)->values()->get($idx % 2);
                $familyPack = $families->get($idx % $families->count());

                $familyAssignment = $idx % 3 === 0 ? $familyPack['family']->id : null; // not all have families
                $parentAssignment = $idx % 3 === 0 ? $familyPack['parent']->id : null;

                return Student::updateOrCreate(
                    ['admission_number' => 'ADM' . str_pad((string) ($idx + 101), 5, '0', STR_PAD_LEFT)],
                    [
                        'name' => $def['first'] . ' ' . $def['last'],
                        'first_name' => $def['first'],
                        'last_name' => $def['last'],
                        'gender' => $def['gender'],
                        'dob' => $def['dob'],
                        'nemis_number' => 'NEMIS' . str_pad((string) ($idx + 5001), 6, '0', STR_PAD_LEFT),
                        'knec_assessment_number' => 'KNEC' . str_pad((string) ($idx + 3001), 6, '0', STR_PAD_LEFT),
                        'classroom_id' => $classroom->id,
                        'stream_id' => $stream?->id,
                        'family_id' => $familyAssignment,
                        'parent_id' => $parentAssignment,
                        'category_id' => $category->id,
                        'status' => 'active',
                        'admission_date' => Carbon::parse("{$academicYear->year}-01-15")->subMonths(rand(0, 8)),
                        'home_address' => 'Nairobi, Kenya',
                        'home_city' => 'Nairobi',
                        'home_county' => 'Nairobi',
                        'home_postal_code' => '00100',
                        'language_preference' => 'English',
                        'blood_group' => 'O+',
                        'allergies' => 'None',
                        'emergency_medical_contact_name' => 'Parent/Guardian',
                        'emergency_medical_contact_phone' => '07' . rand(10, 99) . rand(100000, 999999),
                        'has_special_needs' => false,
                    ]
                );
            });

            // Normalize genders to lowercase (in case of existing records)
            Student::whereNotNull('gender')->get()->each(function ($s) {
                $g = strtolower($s->gender);
                if (!in_array($g, ['male','female'])) {
                    $g = 'other';
                }
                $s->gender = $g;
                $s->save();
            });

            // Academic history and medical records per student
            foreach ($students as $student) {
                StudentAcademicHistory::updateOrCreate(
                    ['student_id' => $student->id, 'academic_year_id' => $academicYear->id, 'term_id' => $terms['Term 3']->id],
                    [
                        'classroom_id' => $student->classroom_id,
                        'stream_id' => $student->stream_id,
                        'enrollment_date' => $student->admission_date ?? Carbon::parse("{$academicYear->year}-01-15"),
                        'promotion_status' => 'promoted',
                        'is_current' => true,
                        'remarks' => 'Auto-seeded academic history record',
                        'teacher_comments' => 'Making steady progress',
                    ]
                );

                StudentMedicalRecord::updateOrCreate(
                    ['student_id' => $student->id, 'record_type' => 'checkup', 'record_date' => Carbon::parse($academicYear->year . '-02-01')],
                    [
                        'title' => 'General checkup',
                        'description' => 'Routine school clinic checkup',
                        'doctor_name' => 'Dr. Aisha Khan',
                        'clinic_hospital' => 'School Clinic',
                        'notes' => 'No concerns noted',
                        'created_by' => $teacherPool->first()?->user_id,
                    ]
                );
            }

            // Extracurricular activities with optional fees
            $clubVotehead = Votehead::updateOrCreate(
                ['code' => 'CLUB'],
                [
                    'name' => 'Clubs & Activities',
                    'description' => 'Optional co-curricular fees',
                    'category' => 'Activities',
                    'is_mandatory' => false,
                    'charge_type' => 'per_student',
                    'is_active' => true,
                ]
            );

            $students->take(8)->each(function (Student $student, int $idx) use ($clubVotehead, $academicYear, $terms, $teacherPool) {
                StudentExtracurricularActivity::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'activity_name' => 'Club ' . ($idx + 1),
                        'activity_type' => 'club',
                    ],
                    [
                        'description' => 'Optional club participation',
                        'start_date' => Carbon::parse($academicYear->year . '-05-01'),
                        'end_date' => Carbon::parse($academicYear->year . '-10-01'),
                        'position_role' => 'Member',
                        'competition_level' => 'School',
                        'fee_amount' => 1500,
                        'votehead_id' => $clubVotehead->id,
                        'auto_bill' => true,
                        'billing_term' => $terms['Term 3']->id,
                        'billing_year' => $academicYear->year,
                        'supervisor_id' => $teacherPool->first()?->user_id,
                        'is_active' => true,
                    ]
                );
            });

            // 7) Attendance for Term 3 (3 months academic days)
            $term3 = $terms['Term 3'];
            $term3Days = collect();
            $cursor = Carbon::parse($term3->start_date);
            while ($cursor->lte(Carbon::parse($term3->end_date))) {
                if (!$cursor->isWeekend()) {
                    $term3Days->push($cursor->copy());
                }
                $cursor->addDay();
            }

            $attendanceStatuses = [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_ABSENT,
            ];

            foreach ($students as $student) {
                foreach ($term3Days as $day) {
                    $status = $attendanceStatuses[array_rand($attendanceStatuses)];
                    $reason = null;
                    $possibleReasons = ['Sick leave', 'Family emergency', 'Travel'];
                    $reason = match ($status) {
                        Attendance::STATUS_ABSENT => $possibleReasons[array_rand($possibleReasons)],
                        Attendance::STATUS_LATE => 'Traffic',
                        default => null,
                    };

                    Attendance::updateOrCreate(
                        ['student_id' => $student->id, 'date' => $day->format('Y-m-d')],
                        [
                            'status' => $status,
                            'reason' => $reason,
                            'reason_code_id' => match ($reason) {
                                'Sick leave' => AttendanceReasonCode::where('code', 'SICK')->value('id'),
                                'Family emergency' => AttendanceReasonCode::where('code', 'FAMILY')->value('id'),
                                'Travel' => AttendanceReasonCode::where('code', 'TRAVEL')->value('id'),
                                'Traffic' => AttendanceReasonCode::where('code', 'TRAFFIC')->value('id'),
                                default => null,
                            },
                            'marked_at' => $day->copy()->setTime(8, rand(0, 40)),
                            'marked_by' => $classTeacherUserIds[$student->classroom_id] ?? $teacherPool->first()?->user_id,
                        ]
                    );
                }
            }

            // 8) Assign teachers to subjects per class
            $assignments = 0;
            foreach ($classrooms as $classroom) {
                foreach ($subjects as $subject) {
                    $teacher = $teacherPool[$assignments % $teacherPool->count()];
                    ClassroomSubject::updateOrCreate(
                        [
                            'classroom_id' => $classroom->id,
                            'subject_id' => $subject->id,
                            'academic_year_id' => $academicYear->id,
                            'term_id' => $term3->id,
                        ],
                        [
                            'staff_id' => $teacher->id,
                            'is_compulsory' => !$subject->is_optional,
                        ]
                    );
                    $assignments++;
                }
            }

            // Assign teachers to streams for stream teacher map page
            foreach ($streams as $stream) {
                $teacher = $teacherPool[$stream->id % $teacherPool->count()];
                DB::table('stream_teacher')->updateOrInsert(
                    ['stream_id' => $stream->id, 'teacher_id' => $teacher->user_id, 'classroom_id' => $stream->classroom_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                DB::table('classroom_stream')->updateOrInsert(
                    ['classroom_id' => $stream->classroom_id, 'stream_id' => $stream->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Exam types
            $examTypes = collect([
                ['code' => 'CAT', 'name' => 'Continuous Assessment', 'calc' => 'average'],
                ['code' => 'OPN', 'name' => 'Opener', 'calc' => 'average'],
                ['code' => 'MID', 'name' => 'Mid Term', 'calc' => 'average'],
                ['code' => 'END', 'name' => 'End Term', 'calc' => 'average'],
            ])->map(fn($et) => ExamType::updateOrCreate(
                ['code' => $et['code']],
                ['name' => $et['name'], 'calculation_method' => $et['calc'], 'default_min_mark' => 0, 'default_max_mark' => 100]
            ));

            // Class timetables (week snapshot)
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
            foreach ($classrooms as $cIdx => $classroom) {
                foreach ($days as $dIdx => $day) {
                    for ($p = 1; $p <= 4; $p++) {
                        Timetable::updateOrCreate(
                            [
                                'classroom_id' => $classroom->id,
                                'academic_year_id' => $academicYear->id,
                                'term_id' => $term3->id,
                                'day' => $day,
                                'period' => $p,
                            ],
                            [
                                'start_time' => Carbon::parse('08:00')->addMinutes(40 * ($p - 1)),
                                'end_time' => Carbon::parse('08:00')->addMinutes(40 * $p),
                                'subject_id' => $subjects[($cIdx + $p) % $subjects->count()]->id,
                                'staff_id' => $teacherPool[($cIdx + $p) % $teacherPool->count()]->id,
                                'room' => 'R-' . ($cIdx + 1),
                                'is_break' => false,
                                'meta' => ['type' => 'class'],
                            ]
                        );
                    }
                }
            }

            // Curriculum designs, schemes, lesson plans, portfolio assessments
            $design = CurriculumDesign::updateOrCreate(
                ['title' => 'CBC Mathematics Design'],
                [
                    'subject_id' => $subjects->first()->id,
                    'class_level' => 'Grade 6',
                    'uploaded_by' => $teacherPool->first()?->user_id,
                    'file_path' => 'documents/curriculum/math.pdf',
                    'pages' => 45,
                    'status' => 'processed',
                    'metadata' => ['version' => '1.0'],
                ]
            );

            foreach ($classrooms->take(2) as $cIdx => $classroom) {
                $scheme = SchemeOfWork::updateOrCreate(
                    [
                        'subject_id' => $subjects[$cIdx]->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $term3->id,
                    ],
                    [
                        'created_by' => $teacherPool[$cIdx]->id,
                        'title' => $subjects[$cIdx]->name . ' Scheme - ' . $term3->name,
                        'description' => 'Auto-seeded scheme of work',
                        'total_lessons' => 12,
                        'lessons_completed' => 4,
                        'status' => 'active',
                        'strands_coverage' => ['Numbers', 'Measurements'],
                        'substrands_coverage' => ['Fractions', 'Area'],
                        'general_remarks' => 'Progressing well',
                    ]
                );

                for ($lp = 1; $lp <= 3; $lp++) {
                    LessonPlan::updateOrCreate(
                        [
                            'scheme_of_work_id' => $scheme->id,
                            'lesson_number' => $lp,
                        ],
                        [
                            'subject_id' => $scheme->subject_id,
                            'classroom_id' => $scheme->classroom_id,
                            'academic_year_id' => $scheme->academic_year_id,
                            'term_id' => $scheme->term_id,
                            'created_by' => $scheme->created_by,
                            'title' => 'Lesson ' . $lp . ': ' . $subjects[$cIdx]->name,
                            'planned_date' => Carbon::parse($term3->start_date)->addDays($lp * 2),
                            'duration_minutes' => 80,
                            'learning_objectives' => ['Objective ' . $lp],
                            'learning_outcomes' => 'Outcome ' . $lp,
                            'learning_resources' => ['Textbook', 'Worksheets'],
                            'activities' => ['Group work', 'Quiz'],
                            'assessment' => 'Short quiz',
                            'status' => 'planned',
                        ]
                    );
                }
            }

            foreach ($students->take(6) as $idx => $student) {
                PortfolioAssessment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subjects[$idx % $subjects->count()]->id,
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $term3->id,
                        'title' => 'Portfolio Task ' . ($idx + 1),
                    ],
                    [
                        'classroom_id' => $student->classroom_id,
                        'portfolio_type' => 'project',
                        'description' => 'CBC project evidence',
                        'total_score' => rand(70, 95),
                        'performance_level_id' => null,
                        'assessed_by' => $teacherPool->first()?->id,
                        'assessment_date' => Carbon::parse($term3->start_date)->addWeeks(2),
                        'status' => 'assessed',
                        'feedback' => 'Good effort',
                    ]
                );
            }

            // Extra-curricular activities (clubs/sports)
            $activityTemplates = [
                ['name' => 'Football Club', 'type' => 'sport', 'day' => 'Tuesday', 'start_time' => '15:30', 'end_time' => '17:00', 'fee' => 2000],
                ['name' => 'Music Club', 'type' => 'club', 'day' => 'Thursday', 'start_time' => '15:00', 'end_time' => '16:30', 'fee' => 1500],
                ['name' => 'Drama', 'type' => 'club', 'day' => 'Monday', 'start_time' => '15:30', 'end_time' => '17:00', 'fee' => 0],
            ];
            foreach ($activityTemplates as $aIdx => $tpl) {
                $activity = \App\Models\Academics\ExtraCurricularActivity::updateOrCreate(
                    [
                        'name' => $tpl['name'],
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $term3->id,
                    ],
                    [
                        'type' => $tpl['type'],
                        'day' => $tpl['day'],
                        'start_time' => $tpl['start_time'],
                        'end_time' => $tpl['end_time'],
                        'period' => $aIdx + 1,
                        'classroom_ids' => $classrooms->pluck('id')->take(4)->toArray(),
                        'staff_ids' => $teacherPool->pluck('id')->take(3)->toArray(),
                        'description' => 'Auto-seeded activity',
                        'is_active' => true,
                        'repeat_weekly' => true,
                        'fee_amount' => $tpl['fee'],
                        'auto_invoice' => $tpl['fee'] > 0,
                        'student_ids' => $students->pluck('id')->shuffle()->take(8)->toArray(),
                    ]
                );
                $activity->syncFinanceIntegration();
            }

            // CBC data: performance levels, competencies, strands/substrands
            $performanceLevels = [
                ['code' => 'E', 'name' => 'Emerging', 'min' => 0, 'max' => 49, 'color' => '#ef4444'],
                ['code' => 'A', 'name' => 'Approaching', 'min' => 50, 'max' => 69, 'color' => '#f59e0b'],
                ['code' => 'P', 'name' => 'Proficient', 'min' => 70, 'max' => 84, 'color' => '#10b981'],
                ['code' => 'X', 'name' => 'Exceeding', 'min' => 85, 'max' => 100, 'color' => '#6366f1'],
            ];
            foreach ($performanceLevels as $idx => $pl) {
                CBCPerformanceLevel::updateOrCreate(
                    ['code' => $pl['code']],
                    [
                        'name' => $pl['name'],
                        'min_percentage' => $pl['min'],
                        'max_percentage' => $pl['max'],
                        'description' => $pl['name'],
                        'color_code' => $pl['color'],
                        'display_order' => $idx + 1,
                        'is_active' => true,
                    ]
                );
            }

            $competencies = [
                ['code' => 'COM', 'name' => 'Communication', 'order' => 1],
                ['code' => 'COL', 'name' => 'Collaboration', 'order' => 2],
                ['code' => 'CRT', 'name' => 'Critical Thinking', 'order' => 3],
                ['code' => 'CRE', 'name' => 'Creativity', 'order' => 4],
            ];
            foreach ($competencies as $comp) {
                CBCCoreCompetency::updateOrCreate(
                    ['code' => $comp['code']],
                    [
                        'name' => $comp['name'],
                        'display_order' => $comp['order'],
                        'is_active' => true,
                    ]
                );
            }

            $mathStrand = CBCStrand::updateOrCreate(
                ['code' => 'MATH-S1'],
                ['name' => 'Numbers and Operations', 'description' => 'Core strand for arithmetic', 'level' => 'primary']
            );
            CBCSubstrand::updateOrCreate(
                ['code' => 'MATH-S1-SS1'],
                ['name' => 'Fractions Basics', 'strand_id' => $mathStrand->id, 'description' => 'Fractions introduction']
            );

            // Events calendar
            $events = [
                ['title' => 'Parent-Teacher Conference', 'type' => 'other', 'start' => "{$year}-09-10", 'end' => "{$year}-09-10"],
                ['title' => 'Science Fair', 'type' => 'other', 'start' => "{$year}-10-05", 'end' => "{$year}-10-05"],
                ['title' => 'Sports Day', 'type' => 'other', 'start' => "{$year}-11-01", 'end' => "{$year}-11-01"],
            ];
            foreach ($events as $ev) {
                Event::updateOrCreate(
                    ['title' => $ev['title'], 'start_date' => $ev['start']],
                    [
                        'description' => $ev['title'],
                        'end_date' => $ev['end'],
                        'start_time' => '09:00',
                        'end_time' => '15:00',
                        'venue' => 'Main Campus',
                        'type' => $ev['type'],
                        'visibility' => 'public',
                        'target_audience' => ['students','parents','staff'],
                        'is_all_day' => false,
                        'is_active' => true,
                        'academic_year_id' => $academicYear->id,
                        'created_by' => $adminUser->id ?? $teacherPool->first()?->user_id,
                    ]
                );
            }

            // 9) Exams, timetables & marks for Term 3
            $exam = Exam::updateOrCreate(
                [
                    'name' => 'Term 3 2025 Opener',
                    'academic_year_id' => $academicYear->id,
                    'term_id' => $term3->id,
                    'classroom_id' => $classrooms->first()->id,
                ],
                [
                    'type' => 'cat',
                    'exam_type_id' => $examTypes->firstWhere('code', 'OPN')?->id,
                    'modality' => 'physical',
                    'stream_id' => null,
                    'subject_id' => $subjects->first()->id,
                    'created_by' => $teacherPool->first()?->user_id,
                    'starts_on' => Carbon::parse($term3->start_date)->addWeeks(1),
                    'ends_on' => Carbon::parse($term3->start_date)->addWeeks(1)->addDays(2),
                    'max_marks' => 100,
                    'weight' => 30,
                    'status' => 'marking',
                ]
            );

            $students->take(15)->each(function (Student $student) use ($exam, $subjects, $teacherPool) {
                ExamMark::updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'subject_id' => $subjects->first()->id,
                    ],
                    [
                        'teacher_id' => $teacherPool->first()->id ?? null,
                        'score_raw' => rand(45, 95),
                        'remark' => 'Normal effort',
                        'status' => 'submitted',
                    ]
                );
            });

            // Report cards for prior term to simulate history
            $prevTerm = $terms['Term 2'];
            $students->take(10)->each(function (Student $student) use ($academicYear, $prevTerm, $classrooms, $teacherPool) {
                $reportCard = ReportCard::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $prevTerm->id,
                    ],
                    [
                        'classroom_id' => $student->classroom_id,
                        'stream_id' => $student->stream_id,
                        'pdf_path' => 'storage/reports/term2_' . $student->id . '.pdf',
                        'published_at' => Carbon::parse($prevTerm->end_date)->addWeek(),
                        'published_by' => $teacherPool->first()?->user_id,
                        'locked_at' => Carbon::parse($prevTerm->end_date)->addWeek(),
                        'summary' => json_encode([
                            'total_marks' => rand(350, 480),
                            'average' => rand(55, 80),
                            'position' => rand(1, 20),
                            'remarks' => 'Steady progress',
                        ]),
                    ]
                );

                $skills = ['Teamwork', 'Communication', 'Critical Thinking', 'Creativity'];
                foreach ($skills as $skill) {
                    ReportCardSkill::updateOrCreate(
                        ['report_card_id' => $reportCard->id, 'skill_name' => $skill],
                        ['rating' => collect(['EE','ME','AE','BE'])->random()]
                    );
                }
            });

            // 10) Behaviours (good & bad)
            $behaviours = collect([
                ['name' => 'Helping peers', 'type' => 'positive', 'description' => 'Assisted classmates with assignments'],
                ['name' => 'Class participation', 'type' => 'positive', 'description' => 'Active in class discussions'],
                ['name' => 'Late to class', 'type' => 'negative', 'description' => 'Arrived late to morning lesson'],
                ['name' => 'Incomplete homework', 'type' => 'negative', 'description' => 'Missed homework submission'],
            ])->map(fn ($b) => Behaviour::updateOrCreate(
                ['name' => $b['name']],
                ['type' => $b['type'], 'description' => $b['description']]
            ));

            // Attach behaviours to students with academic year and term context
            foreach ($students->take(12) as $idx => $student) {
                $behaviour = $behaviours[$idx % $behaviours->count()];
                \App\Models\Academics\StudentBehaviour::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'behaviour_id' => $behaviour->id,
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $term3->id,
                    ],
                    [
                        'notes' => 'Auto-seeded behaviour record',
                        'recorded_by' => $teacherPool->first()?->user_id,
                    ]
                );
            }

            // Homework across subjects/teachers/terms
            $homeworkDates = [
                Carbon::parse($term3->start_date)->addDays(5),
                Carbon::parse($term3->start_date)->addDays(12),
                Carbon::parse($term3->start_date)->addDays(19),
            ];
            foreach ($homeworkDates as $idx => $hwDate) {
                $subject = $subjects[$idx % $subjects->count()];
                $teacher = $teacherPool[$idx % $teacherPool->count()];
                Homework::updateOrCreate(
                    [
                        'classroom_id' => $classrooms[$idx % $classrooms->count()]->id,
                        'stream_id' => $streams[$idx % $streams->count()]->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'title' => $subject->name . ' Homework ' . ($idx + 1),
                    ],
                    [
                        'instructions' => 'Complete assigned exercises and bring to class.',
                        'due_date' => $hwDate->addDays(2),
                        'file_path' => null,
                    ]
                );
            }

            // Diaries: teacher-parent conversations
            $adminUserId = User::orderBy('id')->first()?->id;
            foreach ($students->take(6) as $idx => $student) {
                $diary = StudentDiary::firstOrCreate(['student_id' => $student->id]);
                $teacherUserId = $classTeacherUserIds[$student->classroom_id] ?? $teacherPool->first()?->user_id;

                DiaryEntry::create([
                    'student_diary_id' => $diary->id,
                    'author_id' => $teacherUserId,
                    'author_type' => 'teacher',
                    'content' => 'Discussed progress and upcoming assessments.',
                    'is_read' => true,
                ]);

                DiaryEntry::create([
                    'student_diary_id' => $diary->id,
                    'author_id' => $adminUserId,
                    'author_type' => 'admin',
                    'content' => 'Parent meeting scheduled; please confirm availability.',
                    'is_read' => false,
                ]);
            }

            // Sibling links for students in same family
            foreach ($families as $pack) {
                $familyStudents = $students->filter(fn ($s) => $s->family_id === $pack['family']->id)->values();
                if ($familyStudents->count() > 1) {
                    for ($i = 0; $i < $familyStudents->count(); $i++) {
                        for ($j = $i + 1; $j < $familyStudents->count(); $j++) {
                            DB::table('student_siblings')->updateOrInsert(
                                ['student_id' => $familyStudents[$i]->id, 'sibling_id' => $familyStudents[$j]->id]
                            );
                            DB::table('student_siblings')->updateOrInsert(
                                ['student_id' => $familyStudents[$j]->id, 'sibling_id' => $familyStudents[$i]->id]
                            );
                        }
                    }
                }
            }

            // ---------------- FINANCE MODULE ----------------
            // Voteheads (mandatory + optional)
            $voteheadData = [
                ['code' => 'TUIT', 'name' => 'Tuition', 'amount' => 25000, 'category' => 'Tuition', 'is_mandatory' => true, 'charge_type' => 'per_student'],
                ['code' => 'TRAN', 'name' => 'Transport', 'amount' => 6000, 'category' => 'Transport', 'is_mandatory' => true, 'charge_type' => 'per_student'],
                ['code' => 'HOS', 'name' => 'Hostel', 'amount' => 18000, 'category' => 'Boarding', 'is_mandatory' => false, 'charge_type' => 'per_student'],
                ['code' => 'LIB', 'name' => 'Library', 'amount' => 1500, 'category' => 'Library', 'is_mandatory' => true, 'charge_type' => 'per_student'],
                ['code' => 'ACT', 'name' => 'Activities', 'amount' => 3000, 'category' => 'Activities', 'is_mandatory' => false, 'charge_type' => 'per_student'],
                ['code' => 'LAB', 'name' => 'Lab Fees', 'amount' => 2500, 'category' => 'Science', 'is_mandatory' => true, 'charge_type' => 'per_student'],
            ];
            $voteheads = collect($voteheadData)->map(function ($vh) {
                return Votehead::updateOrCreate(
                    ['code' => $vh['code']],
                    [
                        'name' => $vh['name'],
                        'description' => $vh['name'] . ' fee',
                        'category' => $vh['category'],
                        'is_mandatory' => $vh['is_mandatory'],
                        'charge_type' => $vh['charge_type'],
                        'is_active' => true,
                    ]
                );
            });

            // Payment methods
            $paymentMethods = collect([
                ['code' => 'MPESA', 'name' => 'M-Pesa'],
                ['code' => 'BANK', 'name' => 'Bank Transfer'],
                ['code' => 'CASH', 'name' => 'Cash'],
            ])->map(fn ($pm) => PaymentMethod::updateOrCreate(['code' => $pm['code']], ['name' => $pm['name'], 'is_active' => true]));

            // Fee structures for Term 2 and Term 3 for first three classrooms
            $feeStructures = [];
            foreach ($classrooms as $classroom) {
                foreach (['Term 2', 'Term 3'] as $tName) {
                    $termModel = $terms[$tName];
                    $fs = FeeStructure::updateOrCreate(
                        [
                            'name' => "{$classroom->name} {$tName} Fees",
                            'classroom_id' => $classroom->id,
                            'academic_year_id' => $academicYear->id,
                            'term_id' => $termModel->id,
                        ],
                        [
                            'year' => $academicYear->year,
                            'version' => 1,
                            'is_active' => true,
                            'created_by' => $teacherPool->first()?->user_id,
                        ]
                    );
                    $feeStructures[] = $fs;
                    foreach ($voteheads as $idx => $vh) {
                        FeeCharge::updateOrCreate(
                            [
                                'fee_structure_id' => $fs->id,
                                'votehead_id' => $vh->id,
                            ],
                            [
                                'term' => (int) filter_var($tName, FILTER_SANITIZE_NUMBER_INT),
                                'amount' => $voteheadData[$idx]['amount'],
                            ]
                        );
                    }
                }
            }

            // Invoices for Term 2 and Term 3 with varied payment status
            $invoiceCounter = 800;
            $familiesById = $families->pluck('family', 'family.id');
            foreach ($students as $idx => $student) {
                foreach (['Term 2', 'Term 3'] as $tName) {
                    $termModel = $terms[$tName];
                    $baseVoteheads = $voteheads;
                    // Skip hostel for some day scholars
                    if ($idx % 2 === 1) {
                        $baseVoteheads = $voteheads->reject(fn ($vh) => $vh->code === 'HOS');
                    }
                    $invoiceNumber = 'INV-' . str_pad((string) (++$invoiceCounter), 6, '0', STR_PAD_LEFT);
                    $invoice = Invoice::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_year_id' => $academicYear->id,
                            'term_id' => $termModel->id,
                        ],
                        [
                            'invoice_number' => $invoiceNumber,
                            'year' => $academicYear->year,
                            'term' => (int) filter_var($tName, FILTER_SANITIZE_NUMBER_INT),
                            'issued_date' => $termModel->start_date,
                            'due_date' => Carbon::parse($termModel->start_date)->addWeeks(4),
                            'status' => 'unpaid',
                            'discount_amount' => $idx % 5 === 0 ? 1000 : 0, // occasional early payment discount
                        ]
                    );

                    $total = 0;
                    foreach ($baseVoteheads as $vh) {
                        $amount = $vh->code === 'ACT' && $idx % 3 === 0 ? 0 : ($voteheadData[$voteheads->search($vh)]['amount'] ?? 0);
                        $item = InvoiceItem::updateOrCreate(
                            [
                                'invoice_id' => $invoice->id,
                                'votehead_id' => $vh->id,
                            ],
                            [
                                'amount' => $amount,
                                'discount_amount' => 0,
                                'status' => 'active',
                            ]
                        );
                        $total += $item->amount;

                        // Credit note for a few hostel charges
                        if ($vh->code === 'HOS' && $idx % 7 === 0) {
                            CreditNote::updateOrCreate(
                                ['invoice_id' => $invoice->id, 'invoice_item_id' => $item->id],
                                ['amount' => 2000, 'reason' => 'Hostel rebate']
                            );
                            $total -= 2000;
                        }
                    }

                    // Sibling concession
                    $familyStudentCount = $students->where('family_id', $student->family_id)->count();
                    if ($student->family_id && $familyStudentCount > 1) {
                        FeeConcession::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'family_id' => $student->family_id,
                                'votehead_id' => $voteheads->first()->id,
                                'academic_year_id' => $academicYear->id,
                                'term' => (int) filter_var($tName, FILTER_SANITIZE_NUMBER_INT),
                            ],
                            [
                                'type' => 'percentage',
                                'discount_type' => 'sibling',
                                'frequency' => 'once',
                                'scope' => 'invoice',
                                'value' => 10,
                                'reason' => 'Sibling discount',
                                'is_active' => true,
                                'start_date' => $termModel->start_date,
                            ]
                        );
                        $total *= 0.9;
                    }

                    $total = max(0, $total - ($invoice->discount_amount ?? 0));
                    $invoice->update(['total' => $total, 'balance' => $total, 'paid_amount' => 0]);

                    // Payments: some fully paid, some partial, some unpaid
                    $paymentMethod = $paymentMethods->firstWhere('code', $idx % 2 === 0 ? 'MPESA' : 'BANK');
                    if ($idx % 3 === 0) {
                        $paid = $total;
                    } elseif ($idx % 3 === 1) {
                        $paid = round($total * 0.6, 2);
                    } else {
                        $paid = 0;
                    }

                    if ($paid > 0) {
                        $payment = Payment::updateOrCreate(
                            ['invoice_id' => $invoice->id],
                            [
                                'student_id' => $student->id,
                                'amount' => $paid,
                                'allocated_amount' => $paid,
                                'unallocated_amount' => 0,
                                'payment_method_id' => $paymentMethod?->id,
                                'payer_name' => $student->family?->guardian_name ?? $student->name,
                                'payer_type' => 'parent',
                                'payment_date' => Carbon::parse($termModel->start_date)->addWeeks(2),
                                'narration' => 'Auto-seeded payment',
                            ]
                        );
                        // Allocate across invoice items (pro-rata simple)
                        $remaining = $paid;
                        foreach ($invoice->items as $item) {
                            if ($remaining <= 0) {
                                break;
                            }
                            $alloc = min($item->amount, $remaining);
                            PaymentAllocation::updateOrCreate(
                                ['payment_id' => $payment->id, 'invoice_item_id' => $item->id],
                                [
                                    'amount' => $alloc,
                                    'allocated_at' => $payment->payment_date,
                                    'allocated_by' => $teacherPool->first()?->user_id,
                                ]
                            );
                            $remaining -= $alloc;
                        }
                        $payment->updateAllocationTotals();
                        $invoice->update([
                            'paid_amount' => $payment->allocated_amount,
                            'balance' => max(0, $total - $payment->allocated_amount),
                            'status' => $payment->allocated_amount >= $total ? 'paid' : ($payment->allocated_amount > 0 ? 'partial' : 'unpaid'),
                        ]);
                    }

                    // Payment plans for partly paid/unpaid invoices
                    if ($paid < $total) {
                        $installments = 3;
                        $planTotal = $total - $paid;
                        $start = Carbon::parse($termModel->start_date)->addWeeks(3);
                        $end = Carbon::parse($termModel->end_date)->subWeeks(1);
                        $perInst = round($planTotal / $installments, 2);
                        $plan = \App\Models\FeePaymentPlan::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'invoice_id' => $invoice->id,
                            ],
                            [
                                'total_amount' => $planTotal,
                                'installment_count' => $installments,
                                'installment_amount' => $perInst,
                                'start_date' => $start,
                                'end_date' => $end,
                                'status' => 'active',
                                'notes' => 'Auto-seeded plan for balance',
                                'created_by' => $teacherPool->first()?->user_id,
                            ]
                        );
                        // refresh installments
                        \App\Models\FeePaymentPlanInstallment::where('payment_plan_id', $plan->id)->delete();
                        $current = $start->copy();
                        for ($i = 1; $i <= $installments; $i++) {
                            $amount = $i === $installments ? $planTotal - $perInst * ($installments - 1) : $perInst;
                            \App\Models\FeePaymentPlanInstallment::create([
                                'payment_plan_id' => $plan->id,
                                'installment_number' => $i,
                                'amount' => $amount,
                                'due_date' => $current->copy(),
                                'status' => 'pending',
                            ]);
                            $current->addWeeks(2);
                        }
                    }
                }
            }
        });
    }
}


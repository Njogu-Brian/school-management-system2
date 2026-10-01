<?php

/**
 * School-wide module / sub-module / feature catalog for Settings → Modules & Features.
 * Sidebar sections use the same keys via ModuleAccess + nav_can().
 */
return [
    /**
     * Keys that cannot be disabled (admin must always reach Settings to recover).
     */
    'always_on' => [
        'settings',
        'profile',
    ],

    /**
     * Top-level sidebar modules (match nav_access.sections + school_operations).
     */
    'modules' => [
        'gallery' => [
            'label' => 'Gallery',
            'description' => 'School photo gallery.',
            'group' => 'General',
            'icon' => 'bi-images',
        ],
        'students' => [
            'label' => 'Students',
            'description' => 'Student records, admissions, families, and categories.',
            'group' => 'School Operations',
            'icon' => 'bi-person',
        ],
        'attendance' => [
            'label' => 'Attendance',
            'description' => 'Daily attendance marking and absence reports.',
            'group' => 'School Operations',
            'icon' => 'bi-calendar-check',
        ],
        'academics' => [
            'label' => 'Academics',
            'description' => 'Classrooms, streams, subjects, and promotions.',
            'group' => 'School Operations',
            'icon' => 'bi-book',
        ],
        'cbc' => [
            'label' => 'CBC Curriculum & Planning',
            'description' => 'Curriculum designs, schemes, lesson plans, and portfolios.',
            'group' => 'School Operations',
            'icon' => 'bi-diagram-3',
        ],
        'timetable' => [
            'label' => 'Timetable',
            'description' => 'Class and teacher timetables and activities.',
            'group' => 'School Operations',
            'icon' => 'bi-calendar3',
        ],
        'homework' => [
            'label' => 'Homework & Diaries',
            'description' => 'Homework assignments and digital diaries.',
            'group' => 'School Operations',
            'icon' => 'bi-journal-text',
        ],
        'exams' => [
            'label' => 'Exams',
            'description' => 'Exam types, marks entry, results, and analysis.',
            'group' => 'School Operations',
            'icon' => 'bi-pencil-square',
        ],
        'report_cards' => [
            'label' => 'Report Cards',
            'description' => 'Generate and publish report cards and skills grading.',
            'group' => 'School Operations',
            'icon' => 'bi-file-earmark-text',
        ],
        'assessments' => [
            'label' => 'Assessments',
            'description' => 'Ongoing assessment tools.',
            'group' => 'School Operations',
            'icon' => 'bi-clipboard-check',
        ],
        'behaviours' => [
            'label' => 'Behaviours',
            'description' => 'Behaviour tracking and student behaviour records.',
            'group' => 'School Operations',
            'icon' => 'bi-emoji-smile',
        ],
        'finance' => [
            'label' => 'Finance',
            'description' => 'Fees, invoices, payments, expenses, and financial reports.',
            'group' => 'Finance & Admin',
            'icon' => 'bi-cash-stack',
        ],
        'transport' => [
            'label' => 'Transport',
            'description' => 'Vehicles, trips, pickup points, and driver tools.',
            'group' => 'Finance & Admin',
            'icon' => 'bi-truck',
        ],
        'communication' => [
            'label' => 'Communication',
            'description' => 'SMS, email, WhatsApp, templates, and announcements.',
            'group' => 'Finance & Admin',
            'icon' => 'bi-chat-dots',
        ],
        'hr' => [
            'label' => 'HR / Staff',
            'description' => 'Staff records, leave, attendance, and documents.',
            'group' => 'Finance & Admin',
            'icon' => 'bi-people',
        ],
        'payroll' => [
            'label' => 'Payroll',
            'description' => 'Salary structures, periods, advances, and deductions.',
            'group' => 'Finance & Admin',
            'icon' => 'bi-wallet2',
        ],
        'events' => [
            'label' => 'Events Calendar',
            'description' => 'School events and calendar.',
            'group' => 'Operations',
            'icon' => 'bi-calendar-event',
        ],
        'school_operations' => [
            'label' => 'School Operations',
            'description' => 'Concerns, visitor log, and fixed assets.',
            'group' => 'Operations',
            'icon' => 'bi-building',
        ],
        'inventory' => [
            'label' => 'Inventory & Requirements',
            'description' => 'Inventory items, student requirements, and requisitions.',
            'group' => 'Operations',
            'icon' => 'bi-box-seam',
        ],
        'pos' => [
            'label' => 'Point of Sale',
            'description' => 'Products, orders, discounts, and public shop links.',
            'group' => 'Operations',
            'icon' => 'bi-shop',
        ],
        'documents' => [
            'label' => 'Documents',
            'description' => 'Document library and downloads.',
            'group' => 'Operations',
            'icon' => 'bi-folder',
        ],
        'website_cms' => [
            'label' => 'Website CMS',
            'description' => 'Public school website content management.',
            'group' => 'Operations',
            'icon' => 'bi-globe',
        ],
        'campus_reports' => [
            'label' => 'Campus & Weekly Reports',
            'description' => 'Heatmaps, weekly class/staff reports, and follow-ups.',
            'group' => 'Reports',
            'icon' => 'bi-bar-chart',
        ],
        'settings' => [
            'label' => 'Settings',
            'description' => 'General info, calendar, logs, and system options (always available).',
            'group' => 'System',
            'icon' => 'bi-gear',
            'locked' => true,
        ],
    ],

    /**
     * Nested sidebar areas. Require the parent module to be enabled.
     */
    'submodules' => [
        'students.online_admission' => [
            'parent' => 'students',
            'label' => 'Online Admissions',
            'description' => 'Admin queue for guardian self-service applications.',
            'group' => 'Students',
        ],
        'students.families' => [
            'parent' => 'students',
            'label' => 'Families & Siblings',
            'description' => 'Family linking, integrity reports, and profile update links.',
            'group' => 'Students',
        ],
        'students.categories' => [
            'parent' => 'students',
            'label' => 'Student Categories',
            'description' => 'Category management and bulk category assignment.',
            'group' => 'Students',
        ],
        'students.bulk' => [
            'parent' => 'students',
            'label' => 'Bulk Upload & Import',
            'description' => 'Bulk student upload and update import tools.',
            'group' => 'Students',
        ],
        'attendance.at_risk' => [
            'parent' => 'attendance',
            'label' => 'At-Risk & Consecutive Absences',
            'description' => 'At-risk lists, consecutive absences, and notify recipients.',
            'group' => 'Attendance',
        ],
        'cbc.planning' => [
            'parent' => 'cbc',
            'label' => 'Schemes & Lesson Plans',
            'description' => 'Schemes of work and lesson planning tools.',
            'group' => 'CBC',
        ],
        'cbc.portfolios' => [
            'parent' => 'cbc',
            'label' => 'Portfolio Assessments',
            'description' => 'Portfolio assessment tracking.',
            'group' => 'CBC',
        ],
        'finance.expenses' => [
            'parent' => 'finance',
            'label' => 'Expense Management',
            'description' => 'Expenses, statement analyzer, and accounting/GL.',
            'group' => 'Finance',
        ],
        'finance.mpesa' => [
            'parent' => 'finance',
            'label' => 'M-PESA',
            'description' => 'M-PESA payment reconciliation tools.',
            'group' => 'Finance',
        ],
        'finance.banking' => [
            'parent' => 'finance',
            'label' => 'Banking & Statements',
            'description' => 'Bank accounts, statements, and payment methods.',
            'group' => 'Finance',
        ],
        'finance.extra_income' => [
            'parent' => 'finance',
            'label' => 'Extra Income',
            'description' => 'Trips, swimming, uniforms, and other extra income.',
            'group' => 'Finance',
        ],
        'communication.whatsapp' => [
            'parent' => 'communication',
            'label' => 'WhatsApp',
            'description' => 'WhatsApp send and session setup.',
            'group' => 'Communication',
        ],
        'communication.announcements' => [
            'parent' => 'communication',
            'label' => 'Announcements',
            'description' => 'School announcements board.',
            'group' => 'Communication',
        ],
        'hr.leave' => [
            'parent' => 'hr',
            'label' => 'Leave Management',
            'description' => 'Leave types, requests, and balances.',
            'group' => 'HR',
        ],
        'school_operations.concerns' => [
            'parent' => 'school_operations',
            'label' => 'Concerns',
            'description' => 'Operations concerns tracker.',
            'group' => 'School Operations',
        ],
        'school_operations.visitors' => [
            'parent' => 'school_operations',
            'label' => 'Visitor Log',
            'description' => 'Visitor registration and log.',
            'group' => 'School Operations',
        ],
        'school_operations.assets' => [
            'parent' => 'school_operations',
            'label' => 'Fixed Assets',
            'description' => 'Fixed asset register.',
            'group' => 'School Operations',
        ],
        'inventory.requirements' => [
            'parent' => 'inventory',
            'label' => 'Student Requirements',
            'description' => 'Requirement types, templates, assignments, and fulfilment.',
            'group' => 'Inventory',
        ],
        'pos.public_links' => [
            'parent' => 'pos',
            'label' => 'POS Public Links',
            'description' => 'Public storefront / payment links.',
            'group' => 'Point of Sale',
        ],
        'campus_reports.heatmaps' => [
            'parent' => 'campus_reports',
            'label' => 'Campus Heatmaps',
            'description' => 'Lower and upper campus heatmaps.',
            'group' => 'Reports',
        ],
    ],

    /**
     * Feature flags (behavior, not just nav). Stored as individual settings keys.
     */
    'features' => [
        'enable_online_admission' => [
            'label' => 'Enable Online Admission',
            'description' => 'Turn on the public application form and Online Admissions admin tools.',
            'default' => true,
            'type' => 'bool',
        ],
        'enable_communication_logs' => [
            'label' => 'Enable Communication Logs',
            'description' => 'Show communication delivery logs in the sidebar and allow access.',
            'default' => true,
            'type' => 'bool',
        ],
        'communication_name_style' => [
            'label' => 'Communication name style',
            'description' => 'Default for {{student_name}} / {{staff_name}} in SMS, email, and WhatsApp.',
            'default' => 'full',
            'type' => 'enum',
            'options' => [
                'full' => 'Full name (First Middle Last)',
                'first' => 'First name only',
            ],
        ],
        'google_link_prompt_mode' => [
            'label' => 'Mobile Google link prompt',
            'description' => 'After password/OTP login, ask users to link Google (Skip always available).',
            'default' => 'all',
            'type' => 'enum',
            'options' => [
                'all' => 'Everyone not yet linked',
                'selected' => 'Selected users only',
                'off' => 'Off',
            ],
        ],
    ],

    /**
     * Legacy enabled_modules slugs from the old 9-item UI → new catalog keys.
     */
    'legacy_map' => [
        'attendance' => 'attendance',
        'transport' => 'transport',
        'communication' => 'communication',
        'reports' => 'campus_reports',
        'fees' => 'finance',
        'admissions' => 'students',
        'settings' => 'settings',
        'users' => 'hr',
        // kitchen had no sidebar — dropped
    ],
];

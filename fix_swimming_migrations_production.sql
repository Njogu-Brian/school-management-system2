-- Production Fix Script for Swimming Migrations (SQL Version)
-- Run this directly in your MySQL database if the PHP script doesn't work
-- 
-- This fixes the migration order issue where swimming_ledger
-- was created before swimming_attendance, causing a foreign key constraint error.

-- Step 1: Create swimming_attendance table if it doesn't exist
CREATE TABLE IF NOT EXISTS `swimming_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `classroom_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `session_cost` decimal(10,2) DEFAULT NULL COMMENT 'Per-visit cost at time of attendance',
  `termly_fee_covered` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether covered by termly optional fee',
  `notes` text DEFAULT NULL,
  `marked_by` bigint(20) unsigned DEFAULT NULL,
  `marked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_date` (`student_id`,`attendance_date`),
  KEY `swimming_attendance_student_id_foreign` (`student_id`),
  KEY `swimming_attendance_classroom_id_foreign` (`classroom_id`),
  KEY `swimming_attendance_marked_by_foreign` (`marked_by`),
  KEY `swimming_attendance_classroom_id_attendance_date_index` (`classroom_id`,`attendance_date`),
  KEY `swimming_attendance_attendance_date_index` (`attendance_date`),
  KEY `swimming_attendance_payment_status_index` (`payment_status`),
  CONSTRAINT `swimming_attendance_classroom_id_foreign` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `swimming_attendance_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `swimming_attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Check if swimming_ledger exists and fix it
-- If the table exists but is missing the foreign key, add it
-- If the table doesn't exist, create it

-- First, check if table exists (you may need to manually check this)
-- If swimming_ledger table exists but is broken, you might need to drop it first:
-- DROP TABLE IF EXISTS `swimming_ledger`;

-- Create swimming_ledger table if it doesn't exist
CREATE TABLE IF NOT EXISTS `swimming_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL COMMENT 'Balance after this transaction',
  `source` varchar(255) NOT NULL COMMENT 'transaction, optional_fee, adjustment, attendance',
  `source_id` bigint(20) unsigned DEFAULT NULL COMMENT 'ID of source record (payment_id, optional_fee_id, attendance_id, etc)',
  `source_type` varchar(255) DEFAULT NULL COMMENT 'Model class name for polymorphic relation',
  `swimming_attendance_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `swimming_ledger_student_id_created_at_index` (`student_id`,`created_at`),
  KEY `swimming_ledger_type_created_at_index` (`type`,`created_at`),
  KEY `swimming_ledger_swimming_attendance_id_index` (`swimming_attendance_id`),
  KEY `swimming_ledger_student_id_foreign` (`student_id`),
  KEY `swimming_ledger_created_by_foreign` (`created_by`),
  CONSTRAINT `swimming_ledger_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `swimming_ledger_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `swimming_ledger_swimming_attendance_id_foreign` FOREIGN KEY (`swimming_attendance_id`) REFERENCES `swimming_attendance` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Update migrations table to mark migrations as run
-- Adjust the batch number based on your current highest batch number
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2026_01_15_083721_create_swimming_wallets_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
('2026_01_15_083815_create_swimming_attendance_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
('2026_01_15_083821_create_swimming_ledger_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
('2026_01_15_084857_create_swimming_transaction_allocations_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
('2026_01_15_084913_add_swimming_fields_to_bank_statement_transactions_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m));

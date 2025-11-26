<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookBorrowing;
use App\Models\LibraryCard;
use App\Models\Student;
use App\Models\LibraryFine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LibraryService
{
    protected int $defaultBorrowDays = 14;
    protected float $dailyFineRate = 10.00;

    /**
     * Issue library card to student
     */
    public function issueCard(Student $student, int $validityMonths = 12): LibraryCard
    {
        // Check if student already has a card
        $existingCard = LibraryCard::where('student_id', $student->id)->first();
        if ($existingCard && $existingCard->isActive()) {
            throw new \Exception('Student already has an active library card');
        }

        $card = LibraryCard::create([
            'student_id' => $student->id,
            'card_number' => LibraryCard::generateCardNumber(),
            'issued_date' => now(),
            'expiry_date' => now()->addMonths($validityMonths),
            'status' => 'active',
            'max_borrow_limit' => 3,
            'current_borrow_count' => 0,
        ]);

        return $card;
    }

    /**
     * Borrow a book
     */
    public function borrowBook(BookCopy $copy, LibraryCard $card, ?int $days = null): BookBorrowing
    {
        // Validate card
        if (!$card->canBorrow()) {
            throw new \Exception('Library card is not active or borrow limit reached');
        }

        // Validate copy availability
        if (!$copy->isAvailable()) {
            throw new \Exception('Book copy is not available');
        }

        $borrowDays = $days ?? $this->defaultBorrowDays;

        return DB::transaction(function () use ($copy, $card, $borrowDays) {
            // Create borrowing record
            $borrowing = BookBorrowing::create([
                'book_copy_id' => $copy->id,
                'library_card_id' => $card->id,
                'student_id' => $card->student_id,
                'borrowed_date' => now(),
                'due_date' => now()->addDays($borrowDays),
                'status' => 'borrowed',
                'borrowed_by' => auth()->id(),
            ]);

            // Update copy status
            $copy->update(['status' => 'borrowed']);

            // Update card borrow count
            $card->increment('current_borrow_count');

            // Update book available count
            $copy->book->decrement('available_copies');

            return $borrowing;
        });
    }

    /**
     * Return a book
     */
    public function returnBook(BookBorrowing $borrowing, ?string $condition = null): void
    {
        DB::transaction(function () use ($borrowing, $condition) {
            // Update borrowing
            $borrowing->update([
                'returned_date' => now(),
                'status' => 'returned',
                'returned_by' => auth()->id(),
            ]);

            // Calculate fine if overdue
            if ($borrowing->isOverdue()) {
                $fineAmount = $borrowing->calculateFine($this->dailyFineRate);
                $borrowing->update(['fine_amount' => $fineAmount]);

                // Create fine record
                if ($fineAmount > 0) {
                    LibraryFine::create([
                        'borrowing_id' => $borrowing->id,
                        'student_id' => $borrowing->student_id,
                        'amount' => $fineAmount,
                        'reason' => 'overdue',
                        'status' => 'pending',
                    ]);
                }
            }

            // Update copy status
            $copy = $borrowing->bookCopy;
            $copy->update([
                'status' => 'available',
                'condition' => $condition ?? $copy->condition,
            ]);

            // Update card borrow count
            $borrowing->libraryCard->decrement('current_borrow_count');

            // Update book available count
            $copy->book->increment('available_copies');
        });
    }

    /**
     * Renew a book borrowing
     */
    public function renewBorrowing(BookBorrowing $borrowing, int $additionalDays = null): BookBorrowing
    {
        $days = $additionalDays ?? $this->defaultBorrowDays;

        $borrowing->update([
            'due_date' => $borrowing->due_date->addDays($days),
        ]);

        return $borrowing;
    }

    /**
     * Reserve a book
     */
    public function reserveBook(Book $book, LibraryCard $card): \App\Models\BookReservation
    {
        if (!$card->isActive()) {
            throw new \Exception('Library card is not active');
        }

        // Check if already reserved
        $existing = \App\Models\BookReservation::where('book_id', $book->id)
            ->where('student_id', $card->student_id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new \Exception('Book already reserved by this student');
        }

        return \App\Models\BookReservation::create([
            'book_id' => $book->id,
            'student_id' => $card->student_id,
            'library_card_id' => $card->id,
            'reserved_date' => now(),
            'expiry_date' => now()->addDays(7), // Reservation valid for 7 days
            'status' => 'pending',
        ]);
    }

    /**
     * Get overdue borrowings
     */
    public function getOverdueBorrowings(): \Illuminate\Database\Eloquent\Collection
    {
        return BookBorrowing::where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->with(['student', 'bookCopy.book', 'libraryCard'])
            ->get();
    }

    /**
     * Calculate total fines for student
     */
    public function getStudentFines(Student $student): float
    {
        return LibraryFine::where('student_id', $student->id)
            ->where('status', 'pending')
            ->sum('amount');
    }
}


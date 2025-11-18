<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\JournalService;
use App\Models\Journal;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\Votehead;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class JournalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required models
        $this->student = $this->createStudent();
        $this->votehead = \App\Models\Votehead::factory()->create();
        $this->year = $this->createAcademicYear();
        $this->term = $this->createTerm();
    }

    /** @test */
    public function journal_service_creates_journal_entry()
    {
        $data = [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'debit',
            'amount' => 1000.00,
            'reason' => 'Test journal entry'
        ];

        $journal = JournalService::createAndApply($data);

        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertDatabaseHas('journals', [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'type' => 'debit',
            'amount' => 1000.00
        ]);
    }

    /** @test */
    public function journal_service_creates_invoice_if_not_exists()
    {
        $data = [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'debit',
            'amount' => 1000.00,
            'reason' => 'Test'
        ];

        $journal = JournalService::createAndApply($data);

        $invoice = Invoice::where('student_id', $this->student->id)
            ->where('year', $this->year->year)
            ->where('term', $this->term->name)
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals($this->student->id, $invoice->student_id);
    }

    /** @test */
    public function journal_service_creates_or_updates_invoice_item()
    {
        $data = [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'debit',
            'amount' => 1000.00,
            'reason' => 'Test'
        ];

        $journal = JournalService::createAndApply($data);

        $invoice = Invoice::where('student_id', $this->student->id)->first();
        $item = InvoiceItem::where('invoice_id', $invoice->id)
            ->where('votehead_id', $this->votehead->id)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals(1000.00, $item->amount);
        $this->assertEquals('active', $item->status);
        $this->assertEquals('journal', $item->source);
    }

    /** @test */
    public function journal_service_handles_credit_entries()
    {
        $data = [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'credit',
            'amount' => 500.00,
            'reason' => 'Credit test'
        ];

        $journal = JournalService::createAndApply($data);

        $invoice = Invoice::where('student_id', $this->student->id)->first();
        $item = InvoiceItem::where('invoice_id', $invoice->id)
            ->where('votehead_id', $this->votehead->id)
            ->first();

        $this->assertEquals(-500.00, $item->amount);
    }

    /** @test */
    public function journal_service_updates_existing_invoice_item()
    {
        // Create initial journal entry
        $data1 = [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'debit',
            'amount' => 1000.00,
            'reason' => 'First entry'
        ];
        JournalService::createAndApply($data1);

        // Create second entry for same votehead
        $data2 = [
            'student_id' => $this->student->id,
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'debit',
            'amount' => 500.00,
            'reason' => 'Second entry'
        ];
        JournalService::createAndApply($data2);

        $invoice = Invoice::where('student_id', $this->student->id)->first();
        $item = InvoiceItem::where('invoice_id', $invoice->id)
            ->where('votehead_id', $this->votehead->id)
            ->first();

        // Should be sum of both entries
        $this->assertEquals(1500.00, $item->amount);
    }

    /** @test */
    public function journal_service_uses_transaction()
    {
        // This test verifies that if an error occurs, the transaction is rolled back
        // We'll test by attempting to create a journal with invalid data
        
        $this->expectException(\Exception::class);
        
        $data = [
            'student_id' => 99999, // Non-existent student
            'votehead_id' => $this->votehead->id,
            'year' => $this->year->year,
            'term' => $this->term->name,
            'type' => 'debit',
            'amount' => 1000.00,
            'reason' => 'Test'
        ];

        try {
            JournalService::createAndApply($data);
        } catch (\Exception $e) {
            // Verify no partial data was created
            $this->assertDatabaseMissing('journals', [
                'votehead_id' => $this->votehead->id
            ]);
            throw $e;
        }
    }
}


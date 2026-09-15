<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\Term;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceTermRelationTest extends TestCase
{
    #[Test]
    public function academic_term_returns_the_term_model_when_the_term_column_is_an_integer(): void
    {
        $invoice = Invoice::factory()->create(['term' => 2]);

        $this->assertIsInt((int) $invoice->term);
        $this->assertSame(2, (int) $invoice->getAttribute('term'));

        $term = $invoice->academicTerm();

        $this->assertInstanceOf(Term::class, $term);
        $this->assertSame((int) $invoice->term_id, (int) $term->id);
        $this->assertNotSame('', $invoice->termDisplayLabel());
    }
}

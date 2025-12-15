<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Student, FeeConcession, Votehead, Invoice};

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
    }

    /** @test */
    public function admin_can_access_discounts_page()
    {
        $response = $this->get(route('finance.discounts.index'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.discounts.index');
    }

    /** @test */
    public function admin_can_create_discount()
    {
        $response = $this->get(route('finance.discounts.create'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.discounts.create');
    }

    /** @test */
    public function admin_can_store_discount()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create();

        $response = $this->post(route('finance.discounts.store'), [
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
            'type' => 'percentage',
            'discount_type' => 'manual',
            'frequency' => 'termly',
            'scope' => 'votehead',
            'value' => 10,
            'reason' => 'Test discount',
            'start_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fee_concessions', [
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
            'value' => 10,
        ]);
    }

    /** @test */
    public function admin_can_view_discount_details()
    {
        $student = Student::factory()->create();
        $discount = FeeConcession::factory()->create(['student_id' => $student->id]);

        $response = $this->get(route('finance.discounts.show', $discount));

        $response->assertStatus(200);
        $response->assertViewIs('finance.discounts.show');
        $response->assertViewHas('discount');
    }
}


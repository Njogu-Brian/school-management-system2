<?php

namespace Tests\Unit\Services;

use App\Models\ParentInfo;
use App\Services\FamilyLinkingService;
use Tests\TestCase;

class FamilyLinkingServiceTest extends TestCase
{
    private FamilyLinkingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FamilyLinkingService::class);
    }

    public function test_same_mother_same_father_reuses_parent(): void
    {
        $existing = ParentInfo::query()->create([
            'father_name' => 'Isaac Odhiambo Abuya',
            'father_phone' => '+254732442677',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => '+254720778060',
            'mother_email' => 'laureadhis@gmail.com',
        ]);

        $matched = $this->service->findMatchingParent([
            'father_phone' => '0732442677',
            'mother_phone' => '0720778060',
            'mother_email' => 'laureadhis@gmail.com',
        ]);

        $this->assertNotNull($matched);
        $this->assertSame($existing->id, $matched->id);
    }

    public function test_same_mother_different_father_does_not_reuse_parent(): void
    {
        ParentInfo::query()->create([
            'father_name' => 'Isaac Odhiambo Abuya',
            'father_phone' => '+254732442677',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => '+254720778060',
            'mother_email' => 'laureadhis@gmail.com',
        ]);

        $matched = $this->service->findMatchingParent([
            'father_name' => 'John Mwangi Maina',
            'father_phone' => '+254720778061',
            'mother_phone' => '+254720778060',
            'mother_email' => 'laureadhis@gmail.com',
        ]);

        $this->assertNull($matched);
    }

    public function test_guardian_only_contact_does_not_match(): void
    {
        ParentInfo::query()->create([
            'guardian_name' => 'Rosila Atieno Ondiege',
            'guardian_phone' => '+254722592669',
            'mother_name' => 'Laureen Adhiambo',
            'mother_phone' => '+254720778060',
        ]);

        $matched = $this->service->findMatchingParent([
            'guardian_phone' => '+254722592669',
        ]);

        $this->assertNull($matched);
    }
}

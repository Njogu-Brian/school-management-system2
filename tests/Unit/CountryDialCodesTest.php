<?php

namespace Tests\Unit;

use App\Support\CountryDialCodes;
use Tests\TestCase;

class CountryDialCodesTest extends TestCase
{
    public function test_kenya_is_first_then_remaining_countries_are_alphabetical(): void
    {
        $options = CountryDialCodes::options();

        $this->assertNotEmpty($options);
        $this->assertSame('+254', $options[0]['code']);
        $this->assertSame('Kenya', $options[0]['name']);
        $this->assertSame('Kenya (+254)', $options[0]['label']);

        $names = array_column($options, 'name');
        $this->assertContains('United States', $names);
        $this->assertContains('Zimbabwe', $names);
        $this->assertGreaterThan(180, count($options));

        $rest = array_slice($names, 1);
        $sorted = $rest;
        natcasesort($sorted);
        $this->assertSame(array_values($sorted), array_values($rest));
    }
}

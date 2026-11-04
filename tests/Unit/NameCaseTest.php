<?php

namespace Tests\Unit;

use App\Support\NameCase;
use PHPUnit\Framework\TestCase;

class NameCaseTest extends TestCase
{
    public function test_a_after_an_apostrophe_is_never_capital(): void
    {
        $this->assertSame("Ng'ang'a", NameCase::sentence("ng'ang'a"));
        $this->assertSame("Ng'ang'a", NameCase::sentence("NG'ANG'A"));
        $this->assertSame("Ng'ang'a", NameCase::sentence("Ng'Ang'A"));
        $this->assertSame("Chloe Wambui Ng'ang'a", NameCase::sentence("chloe wambui ng'ang'a"));
    }

    public function test_other_letters_after_an_apostrophe_stay_capital(): void
    {
        $this->assertSame("Mary-Ann O'Connor", NameCase::sentence("MARY-ANN o'connor"));
        $this->assertSame("John Doe", NameCase::sentence("jOhN   DOE"));
    }

    public function test_lower_a_after_apostrophe_leaves_the_rest_of_a_stored_name(): void
    {
        $this->assertSame("Ng'ang'a", NameCase::lowerAAfterApostrophe("Ng'Ang'A"));
        $this->assertSame("O'Connor", NameCase::lowerAAfterApostrophe("O'Connor"));
    }
}

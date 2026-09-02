<?php

namespace Tests\Unit;

use App\Support\PasswordPolicy;
use PHPUnit\Framework\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function test_generated_password_meets_policy(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $password = PasswordPolicy::generate();
            $this->assertTrue(PasswordPolicy::isStrong($password), $password);
            $this->assertGreaterThanOrEqual(8, strlen($password));
        }
    }

    public function test_checklist_flags_missing_parts(): void
    {
        $checks = [];
        foreach (PasswordPolicy::checklist('short') as $item) {
            $checks[$item['id']] = $item;
        }
        $this->assertFalse($checks['length']['ok']);
        $this->assertFalse($checks['upper']['ok']);
        $this->assertTrue($checks['lower']['ok']);
        $this->assertFalse($checks['digit']['ok']);
    }

    public function test_strong_example_passes(): void
    {
        $this->assertTrue(PasswordPolicy::isStrong('School12'));
        $this->assertFalse(PasswordPolicy::isStrong('alllowercase1'));
        $this->assertFalse(PasswordPolicy::isStrong('ALLUPPERCASE1'));
        $this->assertFalse(PasswordPolicy::isStrong('NoDigitsHere'));
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Staff;
use App\Models\ParentInfo;
use Illuminate\Support\Facades\Auth;

class HelpersTest extends TestCase
{
    /** @test */
    public function setting_helper_can_get_and_set_values()
    {
        // Test setting a value
        setting_set('test_key', 'test_value');
        
        // Test getting the value
        $value = setting('test_key');
        $this->assertEquals('test_value', $value);
        
        // Test default value
        $default = setting('non_existent_key', 'default');
        $this->assertEquals('default', $default);
    }

    /** @test */
    public function setting_helper_can_handle_boolean_values()
    {
        setting_set_bool('bool_setting', true);
        $this->assertTrue(setting_bool('bool_setting'));
        
        setting_set_bool('bool_setting', false);
        $this->assertFalse(setting_bool('bool_setting'));
        
        $this->assertFalse(setting_bool('non_existent', false));
    }

    /** @test */
    public function setting_helper_can_handle_integer_values()
    {
        setting_set_int('int_setting', 42);
        $this->assertEquals(42, setting_int('int_setting'));
        
        $this->assertEquals(0, setting_int('non_existent', 0));
    }

    /** @test */
    public function setting_helper_can_increment_values()
    {
        setting_set('counter', '0');
        setting_increment('counter');
        $this->assertEquals('1', setting('counter'));
        
        setting_increment('counter', 5);
        $this->assertEquals('6', setting('counter'));
    }

    /** @test */
    public function replace_placeholders_replaces_school_placeholders()
    {
        setting_set('school_name', 'Test School');
        setting_set('school_phone', '123456789');
        
        $message = replace_placeholders('Welcome to {school_name}. Call us at {school_phone}');
        
        $this->assertStringContainsString('Test School', $message);
        $this->assertStringContainsString('123456789', $message);
    }

    /** @test */
    public function replace_placeholders_replaces_student_placeholders()
    {
        $student = $this->createStudent([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'ST001'
        ]);
        
        $classroom = $this->createClassroom(['name' => 'Grade 1']);
        $student->classroom_id = $classroom->id;
        $student->save();
        
        $message = replace_placeholders('Hello {student_name}, your class is {class_name}', $student);
        
        $this->assertStringContainsString('John', $message);
        $this->assertStringContainsString('Grade 1', $message);
    }

    /** @test */
    public function replace_placeholders_replaces_staff_placeholders()
    {
        $staff = Staff::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]);
        
        $message = replace_placeholders('Staff member: {staff_name}', $staff);
        
        $this->assertStringContainsString('Jane', $message);
        $this->assertStringContainsString('Smith', $message);
    }

    /** @test */
    public function can_access_returns_true_for_super_admin()
    {
        $admin = $this->createUser([], 'Super Admin');
        
        $this->actingAs($admin);
        $this->assertTrue(can_access('any.permission'));
    }

    /** @test */
    public function can_access_returns_false_for_unauthorized_user()
    {
        $user = $this->createUser();
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'test.permission']);
        
        $this->actingAs($user);
        $this->assertFalse(can_access('test.permission'));
    }

    /** @test */
    public function can_access_returns_true_when_user_has_permission()
    {
        $user = $this->createUser();
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'test.permission']);
        $user->givePermissionTo($permission);
        
        $this->actingAs($user);
        $this->assertTrue(can_access('test.permission'));
    }

    /** @test */
    public function format_number_formats_numbers_correctly()
    {
        $this->assertEquals('1,234', format_number(1234));
        $this->assertEquals('1,234.56', format_number(1234.56, 2));
        $this->assertEquals('0', format_number(0));
    }

    /** @test */
    public function format_money_formats_currency_correctly()
    {
        $formatted = format_money(1234.56, 'KES');
        $this->assertStringContainsString('1234', $formatted);
        $this->assertStringContainsString('KES', $formatted);
    }

    /** @test */
    public function system_setting_helper_works_as_alias()
    {
        setting_set('legacy_key', 'legacy_value');
        $this->assertEquals('legacy_value', system_setting('legacy_key'));
    }
}


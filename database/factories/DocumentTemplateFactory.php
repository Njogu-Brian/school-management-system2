<?php

namespace Database\Factories;

use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentTemplateFactory extends Factory
{
    protected $model = DocumentTemplate::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true) . ' Template';
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(4),
            'type' => $this->faker->randomElement([
                'certificate',
                'transcript',
                'id_card',
                'transfer_certificate',
                'character_certificate',
                'diploma',
                'merit_certificate',
                'participation_certificate',
                'custom',
            ]),
            'template_html' => '<html><body><h1>{{student_name}}</h1><p>Certificate Content</p></body></html>',
            'placeholders' => [
                'student_name',
                'student_admission_number',
                'current_date',
            ],
            'settings' => [
                'paper_size' => 'A4',
                'orientation' => 'portrait',
            ],
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}


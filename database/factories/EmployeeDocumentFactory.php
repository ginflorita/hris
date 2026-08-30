<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'document_type' => DocumentType::Other,
            'title' => fake()->words(3, true),
            'file_path' => 'employee-documents/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'uploaded_by' => null,
        ];
    }
}

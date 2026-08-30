<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAddress>
 */
class EmployeeAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(AddressType::cases()),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'city' => fake()->city(),
            'province_state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => 'Philippines',
            'is_primary' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'name' => fake()->unique()->city().' Site',
            'code' => fake()->unique()->regexify('[A-Z]{2,5}'),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    /**
     * Attach to a branch, inheriting that branch's company.
     */
    public function inBranch(Branch $branch): static
    {
        return $this->state([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
        ]);
    }
}

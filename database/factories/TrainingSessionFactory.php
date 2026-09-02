<?php

namespace Database\Factories;

use App\Enums\TrainingSessionStatus;
use App\Models\TrainingCourse;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 *
 * `company_id` is read from a concrete, already-created course rather than
 * set independently -- a mismatch here (session.company_id !== course's
 * own company) is exactly the shape of bug 15a's PerformanceGoalFactory
 * originally had, caught only by a test reusing the mismatched IDs.
 */
class TrainingSessionFactory extends Factory
{
    public function definition(): array
    {
        $course = TrainingCourse::factory()->create();

        return [
            'company_id' => $course->company_id,
            'training_course_id' => $course->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'status' => TrainingSessionStatus::Scheduled,
        ];
    }

    public function forCourse(TrainingCourse $course): static
    {
        return $this->state([
            'training_course_id' => $course->id,
            'company_id' => $course->company_id,
        ]);
    }
}

<?php

namespace App\Models;

use App\Enums\ApplicantSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone',
        'resume_path', 'resume_original_filename', 'source', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'source' => ApplicantSource::class,
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}

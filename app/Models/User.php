<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\DefaultRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * is_system_account/is_protected are deliberately NOT here — the one
     * place that sets them (DatabaseSeeder) goes through a factory, which
     * bypasses $fillable entirely, so they don't need to be. Keeping them
     * off this list means a stray $user->update($request->all()) can
     * never grant Superadmin-style protection to an arbitrary account.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'disabled_at',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'disabled_at' => 'datetime',
            'is_system_account' => 'boolean',
            'is_protected' => 'boolean',
        ];
    }

    public function isDisabled(): bool
    {
        return ! is_null($this->disabled_at);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole(DefaultRole::Superadmin->value);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

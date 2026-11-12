<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'primary_contact_number',
    'secondary_contact_number',
    'home_address',
    'avatar_url',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

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
        ];
    }

    /**
     * Determine if the user can access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        if ($this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        return (bool) $this->hasRole(Utils::getPanelUserRoleName());
    }

    /**
     * Get user's avatar URL for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_url);
    }

    /**
     * @return HasMany<UserPaymentOption, covariant $this>
     */
    public function paymentOptions(): HasMany
    {
        return $this->hasMany(UserPaymentOption::class);
    }
}

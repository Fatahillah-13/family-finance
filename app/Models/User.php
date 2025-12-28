<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active_household_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }
    public function activeHousehold(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'active_household_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(HouseholdMembership::class);
    }

    public function activeMembership(): ?HouseholdMembership
    {
        if (!$this->active_household_id) {
            return null;
        }

        return $this->memberships()
            ->with(['role.permissions'])
            ->where('household_id', $this->active_household_id)
            ->first();
    }

    public function hasPermission(string $permissionKey): bool
    {
        $membership = $this->activeMembership();
        if (!$membership) {
            return false;
        }

        // Role permissions are per household
        return $membership->role
            ->permissions
            ->contains('key', $permissionKey);
    }

    public function isOwner(): bool
    {
        $membership = $this->activeMembership();
        return $membership?->role?->name === 'Owner';
    }
}

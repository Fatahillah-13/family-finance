<?php

namespace App\Listeners;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Services\HouseholdCreator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateDefaultHouseholdForNewUser
{
    public function __construct(
        private HouseholdCreator $creator
    ) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        if ($user->active_household_id) {
            return;
        }

        $this->creator->createForOwner(
            ownerUser: $user,
            name: 'Keluarga ' . ($user->name ?? 'Baru'),
            currency: 'IDR',
        );
    }
}

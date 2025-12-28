<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HouseholdCreator
{
    /**
     * Create a household and attach $ownerUser as "Owner", plus seed default roles.
     */
    public function createForOwner(User $ownerUser, string $name, string $currency = 'IDR'): Household
    {
        return DB::transaction(function () use ($ownerUser, $name, $currency) {
            $household = Household::create([
                'name' => $name,
                'currency' => $currency,
            ]);

            $ownerRole = Role::create(['household_id' => $household->id, 'name' => 'Owner']);
            $adminRole = Role::create(['household_id' => $household->id, 'name' => 'Admin']);
            $memberRole = Role::create(['household_id' => $household->id, 'name' => 'Member']);
            $viewerRole = Role::create(['household_id' => $household->id, 'name' => 'Viewer']);

            $allPermissions = Permission::query()->pluck('id', 'key');

            // Owner: all
            $ownerRole->permissions()->sync($allPermissions->values());

            // Admin: all except role/permission mgmt (owner-only)
            $adminAllowed = $allPermissions->except(['roles.manage', 'permissions.assign']);
            $adminRole->permissions()->sync($adminAllowed->values());

            // Member
            $memberAllowed = $allPermissions->only([
                'household.read',
                'accounts.read',
                'categories.read',
                'tags.read',
                'transactions.read',
                'transactions.create',
                'transactions.update',
                'budgets.read',
                'reports.read',
            ]);
            $memberRole->permissions()->sync($memberAllowed->values());

            // Viewer
            $viewerAllowed = $allPermissions->only([
                'household.read',
                'accounts.read',
                'categories.read',
                'tags.read',
                'transactions.read',
                'budgets.read',
                'reports.read',
            ]);
            $viewerRole->permissions()->sync($viewerAllowed->values());

            // Seed categories default
            $this->seedDefaultCategories($household);

            HouseholdMembership::create([
                'household_id' => $household->id,
                'user_id' => $ownerUser->id,
                'role_id' => $ownerRole->id,
                'status' => 'active',
            ]);

            // Set as active household
            $ownerUser->active_household_id = $household->id;
            $ownerUser->save();

            return $household;
        });
    }

    private function seedDefaultCategories(Household $household): void
    {
        // Income
        $income = [
            'Gaji',
            'Bonus',
            'Usaha',
            'Hadiah',
            'Lainnya',
        ];

        // Expense (with some subcategories)
        $expenseTree = [
            'Makan & Minum' => ['Belanja Harian', 'Jajan', 'Makan di Luar'],
            'Transport' => ['Bensin', 'Parkir', 'Servis Kendaraan', 'Ojek/Taxi'],
            'Rumah Tangga' => ['Listrik', 'Air', 'Internet', 'Gas', 'Perlengkapan Rumah'],
            'Kesehatan' => ['Obat', 'Dokter', 'Asuransi'],
            'Pendidikan' => ['SPP', 'Buku', 'Kursus'],
            'Hiburan' => ['Streaming', 'Liburan'],
            'Lainnya' => [],
        ];

        foreach ($income as $name) {
            Category::firstOrCreate([
                'household_id' => $household->id,
                'type' => 'income',
                'name' => $name,
                'parent_id' => null,
            ], [
                'is_active' => true,
            ]);
        }

        foreach ($expenseTree as $parentName => $children) {
            $parent = Category::firstOrCreate([
                'household_id' => $household->id,
                'type' => 'expense',
                'name' => $parentName,
                'parent_id' => null,
            ], [
                'is_active' => true,
            ]);

            foreach ($children as $childName) {
                Category::firstOrCreate([
                    'household_id' => $household->id,
                    'type' => 'expense',
                    'name' => $childName,
                    'parent_id' => $parent->id,
                ], [
                    'is_active' => true,
                ]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['key' => 'household.read', 'name' => 'View household', 'group' => 'Household'],
            ['key' => 'household.update', 'name' => 'Update household', 'group' => 'Household'],
            ['key' => 'household.members.manage', 'name' => 'Manage members', 'group' => 'Household'],

            ['key' => 'roles.manage', 'name' => 'Manage roles', 'group' => 'Access Control'],
            ['key' => 'permissions.assign', 'name' => 'Assign permissions', 'group' => 'Access Control'],

            ['key' => 'accounts.read', 'name' => 'View accounts', 'group' => 'Accounts'],
            ['key' => 'accounts.manage', 'name' => 'Manage accounts', 'group' => 'Accounts'],

            ['key' => 'categories.read', 'name' => 'View categories', 'group' => 'Categories'],
            ['key' => 'categories.manage', 'name' => 'Manage categories', 'group' => 'Categories'],

            ['key' => 'tags.read', 'name' => 'View tags', 'group' => 'Tags'],
            ['key' => 'tags.manage', 'name' => 'Manage tags', 'group' => 'Tags'],

            ['key' => 'transactions.read', 'name' => 'View transactions', 'group' => 'Transactions'],
            ['key' => 'transactions.create', 'name' => 'Create transactions', 'group' => 'Transactions'],
            ['key' => 'transactions.update', 'name' => 'Update transactions', 'group' => 'Transactions'],
            ['key' => 'transactions.delete', 'name' => 'Delete transactions', 'group' => 'Transactions'],
            ['key' => 'transactions.import', 'name' => 'Import transactions (CSV)', 'group' => 'Transactions'],

            ['key' => 'budgets.read', 'name' => 'View budgets', 'group' => 'Budgets'],
            ['key' => 'budgets.manage', 'name' => 'Manage budgets', 'group' => 'Budgets'],

            ['key' => 'reports.read', 'name' => 'View reports', 'group' => 'Reports'],
            ['key' => 'reports.export', 'name' => 'Export reports', 'group' => 'Reports'],

            ['key' => 'backup.export', 'name' => 'Export backup', 'group' => 'Backup'],
            ['key' => 'backup.import', 'name' => 'Import backup', 'group' => 'Backup'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['key' => $p['key']], $p);
        }
    }
}

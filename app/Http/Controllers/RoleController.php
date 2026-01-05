<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $roles = Role::query()
            ->with('permissions')
            ->where('household_id', $hid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->orderBy('key')
            ->get();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        Role::create([
            'household_id' => $hid,
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return redirect()->route('roles.index');
    }

    public function update(Request $request, Role $role)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $role->household_id === $hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        $role->update(['name' => $validated['name']]);

        return back();
    }

    public function toggle(Request $request, Role $role)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $role->household_id === $hid, 403);

        // Optional safety: prevent disabling Owner role
        if ($role->name === 'Owner') {
            return back()->withErrors(['role' => 'Role Owner tidak boleh dinonaktifkan.']);
        }

        $role->is_active = !$role->is_active;
        $role->save();

        return back();
    }

    public function syncPermissions(Request $request, Role $role)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $role->household_id === $hid, 403);

        // Optional safety: Owner role always has all permissions
        if ($role->name === 'Owner') {
            $all = Permission::query()->pluck('id')->all();
            $role->permissions()->sync($all);
            return back();
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return back();
    }
}

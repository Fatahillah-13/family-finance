<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\Role;
use App\Services\Audit;
use App\Models\HouseholdInvitation;
use App\Models\User;

class HouseholdMemberController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $householdId = $user->active_household_id;

        abort_unless($householdId, 403);

        $household = Household::findOrFail($householdId);

        $memberships = HouseholdMembership::query()
            ->with(['user', 'role'])
            ->where('household_id', $householdId)
            ->orderBy('id')
            ->get();

        $roles = Role::query()
            ->where('household_id', $householdId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $invitations = HouseholdInvitation::query()
            ->with('role')
            ->where('household_id', $householdId)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('households.members', compact('household', 'memberships', 'roles', 'invitations'));
    }

    // public function store(Request $request)
    // {
    //     /** @var User $actor */
    //     $actor = $request->user();
    //     $householdId = $actor->active_household_id;

    //     abort_unless($householdId, 403);

    //     $validated = $request->validate([
    //         'email' => ['required', 'email'],
    //         'role_id' => ['required', 'integer', 'exists:roles,id'],
    //     ]);

    //     // Pastikan role milik household aktif
    //     $role = Role::query()
    //         ->where('household_id', $householdId)
    //         ->where('id', $validated['role_id'])
    //         ->firstOrFail();

    //     $targetUser = User::query()
    //         ->where('email', $validated['email'])
    //         ->first();

    //     if (!$targetUser) {
    //         return back()
    //             ->withErrors(['email' => 'Email belum terdaftar. Minta anggota register terlebih dahulu.'])
    //             ->onlyInput('email');
    //     }

    //     $membership = HouseholdMembership::updateOrCreate(
    //         ['household_id' => $householdId, 'user_id' => $targetUser->id],
    //         ['role_id' => $role->id, 'status' => 'active']
    //     );

    //     Audit::log($householdId, $actor, 'members.add', 'HouseholdMembership', $membership->id, [
    //         'member_user_id' => $targetUser->id,
    //         'member_email' => $targetUser->email,
    //         'role_id' => $role->id,
    //         'role_name' => $role->name,
    //     ]);

    //     return redirect()->route('households.members');
    // }

    public function destroy(Request $request, HouseholdMembership $membership)
    {
        /** @var User $actor */
        $actor = $request->user();
        $householdId = $actor->active_household_id;

        abort_unless($householdId, 403);
        abort_unless($membership->household_id === $householdId, 403);

        $membership->loadMissing(['user', 'role']);

        // Basic safety: jangan biarkan user menghapus dirinya sendiri lewat endpoint ini (opsional)
        if ($membership->user_id === $actor->id) {
            return back()->withErrors(['member' => 'Tidak bisa menghapus diri sendiri dari household aktif lewat menu ini.']);
        }

        $meta = [
            'member_user_id' => $membership->user_id,
            'member_email' => $membership->user?->email,
            'old_role_id' => $membership->role_id,
            'old_role_name' => $membership->role?->name,
        ];

        $membershipId = $membership->id;
        $membership->delete();

        Audit::log($householdId, $actor, 'members.remove', 'HouseholdMembership', $membershipId, $meta);

        return redirect()->route('households.members');
    }

    public function updateRole(Request $request, HouseholdMembership $membership)
    {
        /** @var User $actor */
        $actor = $request->user();
        $hid = $actor->active_household_id;

        abort_unless($hid, 403);
        abort_unless($membership->household_id === $hid, 403);

        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $membership->loadMissing(['user', 'role']);

        $newRole = Role::query()
            ->where('household_id', $hid)
            ->where('id', $validated['role_id'])
            ->where('is_active', true)
            ->firstOrFail();

        // Safety: jangan ubah role sendiri dari Owner -> lainnya (biar gak lockout)
        if ($membership->user_id === $actor->id && $membership->role?->name === 'Owner' && $newRole->name !== 'Owner') {
            return back()->withErrors(['member' => 'Tidak boleh menurunkan role Owner untuk diri sendiri.']);
        }

        $oldRoleId = $membership->role_id;
        $oldRoleName = $membership->role?->name;

        $membership->role_id = $newRole->id;
        $membership->save();

        Audit::log($hid, $actor, 'members.role.update', 'HouseholdMembership', $membership->id, [
            'member_user_id' => $membership->user_id,
            'member_email' => $membership->user?->email,
            'old_role_id' => $oldRoleId,
            'old_role_name' => $oldRoleName,
            'new_role_id' => $newRole->id,
            'new_role_name' => $newRole->name,
        ]);

        return back();
    }
}

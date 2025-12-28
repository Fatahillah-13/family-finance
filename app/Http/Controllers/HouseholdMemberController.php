<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\Role;
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
            ->orderBy('name')
            ->get();

        return view('households.members', compact('household', 'memberships', 'roles'));
    }

    public function store(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();
        $householdId = $actor->active_household_id;

        abort_unless($householdId, 403);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        // Pastikan role milik household aktif
        $role = Role::query()
            ->where('household_id', $householdId)
            ->where('id', $validated['role_id'])
            ->firstOrFail();

        $targetUser = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (!$targetUser) {
            return back()
                ->withErrors(['email' => 'Email belum terdaftar. Minta anggota register terlebih dahulu.'])
                ->onlyInput('email');
        }

        HouseholdMembership::updateOrCreate(
            ['household_id' => $householdId, 'user_id' => $targetUser->id],
            ['role_id' => $role->id, 'status' => 'active']
        );

        return redirect()->route('households.members');
    }

    public function destroy(Request $request, HouseholdMembership $membership)
    {
        /** @var User $actor */
        $actor = $request->user();
        $householdId = $actor->active_household_id;

        abort_unless($householdId, 403);
        abort_unless($membership->household_id === $householdId, 403);

        // Basic safety: jangan biarkan user menghapus dirinya sendiri lewat endpoint ini (opsional)
        if ($membership->user_id === $actor->id) {
            return back()->withErrors(['member' => 'Tidak bisa menghapus diri sendiri dari household aktif lewat menu ini.']);
        }

        $membership->delete();

        return redirect()->route('households.members');
    }
}

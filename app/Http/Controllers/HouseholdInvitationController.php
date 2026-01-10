<?php

namespace App\Http\Controllers;

use App\Models\HouseholdInvitation;
use App\Models\HouseholdMembership;
use App\Models\Role;
use App\Models\User;
use App\Notifications\HouseholdInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HouseholdInvitationController extends Controller
{
    public function store(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();
        $hid = $actor->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::query()
            ->where('household_id', $hid)
            ->where('id', $validated['role_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $invitation = HouseholdInvitation::create([
            'household_id' => $hid,
            'inviter_user_id' => $actor->id,
            'email' => strtolower($validated['email']),
            'role_id' => $role->id,
            'token' => Str::random(48),
            'status' => 'pending',
            'sent_at' => now(),
            'expires_at' => now()->addDays(2),
        ]);

        // Kirim email invitation
        // Kita gunakan "AnonymousNotifiable" biar bisa kirim ke email yg belum jadi user
        \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
            ->notify(new HouseholdInvitationNotification($invitation->loadMissing(['household', 'role'])));

        return back()->with('status', 'Invitation sent.');
    }

    public function show(Request $request, string $token)
    {
        /** @var User $user */
        $user = $request->user();

        $invitation = HouseholdInvitation::query()
            ->with(['household', 'inviter', 'role'])
            ->where('token', $token)
            ->firstOrFail();

        // expired auto handling
        if ($invitation->status === 'pending' && $invitation->expires_at && now()->gt($invitation->expires_at)) {
            $invitation->status = 'expired';
            $invitation->save();
        }

        // hanya pending yang bisa diproses
        if ($invitation->status !== 'pending') {
            return view('invitations.show', compact('invitation'))
                ->with('status', 'Invitation is not pending.');
        }

        // keamanan: harus login dengan email yg sama
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            abort(403, 'This invitation is for a different email address.');
        }

        return view('invitations.show', compact('invitation'));
    }

    public function accept(Request $request, string $token)
    {
        /** @var User $user */
        $user = $request->user();

        $invitation = HouseholdInvitation::query()
            ->with(['role'])
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->status !== 'pending') {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'Invitation is not pending.']);
        }

        if ($invitation->expires_at && now()->gt($invitation->expires_at)) {
            $invitation->status = 'expired';
            $invitation->save();

            return redirect()->route('dashboard')->withErrors(['invitation' => 'Invitation expired.']);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            abort(403);
        }

        // Buat membership (status bisa Anda set 'active' atau default sesuai sistem Anda)
        HouseholdMembership::firstOrCreate(
            [
                'household_id' => $invitation->household_id,
                'user_id' => $user->id,
            ],
            [
                'role_id' => $invitation->role_id,
                'status' => 'active', // sesuaikan jika enum Anda berbeda
            ]
        );

        // Update invitation
        $invitation->status = 'accepted';
        $invitation->responded_at = now();
        $invitation->save();

        // Auto switch household
        $user->active_household_id = $invitation->household_id;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Invitation accepted. Switched household.');
    }

    public function reject(Request $request, string $token)
    {
        /** @var User $user */
        $user = $request->user();

        $invitation = HouseholdInvitation::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->status !== 'pending') {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'Invitation is not pending.']);
        }

        if ($invitation->expires_at && now()->gt($invitation->expires_at)) {
            $invitation->status = 'expired';
            $invitation->save();

            return redirect()->route('dashboard')->withErrors(['invitation' => 'Invitation expired.']);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            abort(403);
        }

        $invitation->status = 'rejected';
        $invitation->responded_at = now();
        $invitation->save();

        return redirect()->route('dashboard')->with('status', 'Invitation rejected.');
    }
}

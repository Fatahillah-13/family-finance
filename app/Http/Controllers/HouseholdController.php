<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Household;
use App\Models\User;
use App\Services\HouseholdCreator;
use Illuminate\Support\Facades\Auth;

class HouseholdController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $households = Household::query()
            ->whereIn('id', $user->memberships()->pluck('household_id'))
            ->orderBy('name')
            ->get();

        return view('households.index', [
            'households' => $households,
            'activeHouseholdId' => $user->active_household_id,
        ]);
    }

    public function store(Request $request, HouseholdCreator $creator)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $creator->createForOwner($user, $validated['name'], 'IDR');

        return redirect()->route('households.index');
    }

    public function switch(Request $request, Household $household)
    {
        /** @var User $user */
        $user = $request->user();

        $isMember = $user->memberships()
            ->where('household_id', $household->id)
            ->exists();

        abort_unless($isMember, 403);

        $user->active_household_id = $household->id;
        $user->save();

        return redirect()->route('dashboard');
    }
}

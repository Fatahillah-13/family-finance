<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public array $types = ['cash', 'bank', 'ewallet', 'other'];

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $accounts = Account::query()
            ->where('household_id', $hid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('accounts.index', [
            'accounts' => $accounts,
            'types' => $this->types,
        ]);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        Account::create([
            'household_id' => $hid,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'note' => $validated['note'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('accounts.index');
    }

    public function update(Request $request, Account $account)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $account->household_id === $hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index');
    }

    public function toggle(Request $request, Account $account)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $account->household_id === $hid, 403);

        $account->is_active = !$account->is_active;
        $account->save();

        return redirect()->route('accounts.index');
    }
}

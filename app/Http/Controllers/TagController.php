<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $tags = Tag::query()
            ->where('household_id', $hid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        Tag::updateOrCreate(
            ['household_id' => $hid, 'name' => $validated['name']],
            ['is_active' => true]
        );

        return redirect()->route('tags.index');
    }

    public function update(Request $request, Tag $tag)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $tag->household_id === $hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $tag->update($validated);

        return redirect()->route('tags.index');
    }

    public function toggle(Request $request, Tag $tag)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $tag->household_id === $hid, 403);

        $tag->is_active = !$tag->is_active;
        $tag->save();

        return redirect()->route('tags.index');
    }
}

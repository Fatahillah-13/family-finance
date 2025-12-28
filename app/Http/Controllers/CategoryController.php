<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $type = $request->query('type', 'expense');
        abort_unless(in_array($type, ['income', 'expense'], true), 404);

        $categories = Category::query()
            ->where('household_id', $hid)
            ->where('type', $type)
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderByDesc('is_active')->orderBy('name')])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $parents = Category::query()
            ->where('household_id', $hid)
            ->where('type', $type)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories', 'parents', 'type'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parent = Category::query()
                ->where('household_id', $hid)
                ->where('type', $validated['type'])
                ->whereNull('parent_id')
                ->where('id', $parentId)
                ->firstOrFail();
            $parentId = $parent->id;
        }

        Category::create([
            'household_id' => $hid,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'parent_id' => $parentId,
            'is_active' => true,
        ]);

        return redirect()->route('categories.index', ['type' => $validated['type']]);
    }

    public function update(Request $request, Category $category)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $category->household_id === $hid, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $category->update($validated);

        return back();
    }

    public function toggle(Request $request, Category $category)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $category->household_id === $hid, 403);

        $category->is_active = !$category->is_active;
        $category->save();

        return back();
    }
}

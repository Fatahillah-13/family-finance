<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $month = $request->query('month', Carbon::now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) === 1, 422);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $budgets = Budget::query()
            ->with('category')
            ->where('household_id', $hid)
            ->where('month', $month)
            ->orderByDesc('is_active')
            ->get();

        // spent per category for this month (expense only)
        $spentByCategory = DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('household_id', $hid)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->pluck('total', 'category_id');

        $expenseCategories = Category::query()
            ->where('household_id', $hid)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('budgets.index', compact('month', 'budgets', 'spentByCategory', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'category_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        // ensure category is expense + same household
        $category = Category::query()
            ->where('household_id', $hid)
            ->where('type', 'expense')
            ->where('id', $validated['category_id'])
            ->firstOrFail();

        Budget::updateOrCreate(
            ['household_id' => $hid, 'category_id' => $category->id, 'month' => $validated['month']],
            ['amount' => (int) $validated['amount'], 'is_active' => true]
        );

        return redirect()->route('budgets.index', ['month' => $validated['month']]);
    }

    public function update(Request $request, Budget $budget)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $budget->household_id === $hid, 403);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $budget->amount = (int) $validated['amount'];
        $budget->save();

        return back();
    }

    public function toggle(Request $request, Budget $budget)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $budget->household_id === $hid, 403);

        $budget->is_active = !$budget->is_active;
        $budget->save();

        return back();
    }
}

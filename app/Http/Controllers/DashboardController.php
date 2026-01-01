<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        // Accounts + current balances (computed)
        $accounts = Account::query()
            ->where('household_id', $hid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $accountBalances = $accounts->map(function (Account $a) {
            return [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'is_active' => $a->is_active,
                'balance' => $a->currentBalance(),
            ];
        });

        // Monthly totals (occurred_at)
        $monthlyIncome = (int) Transaction::query()
            ->where('household_id', $hid)
            ->whereNull('deleted_at')
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $monthlyExpense = (int) Transaction::query()
            ->where('household_id', $hid)
            ->whereNull('deleted_at')
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $monthlyNet = $monthlyIncome - $monthlyExpense;

        // Top categories (expense)
        $topExpenseCategories = DB::table('transactions')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.household_id', $hid)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.occurred_at', [$start, $end])
            ->groupBy('transactions.category_id', 'categories.name')
            ->selectRaw('categories.name as category_name, SUM(transactions.amount) as total')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Recent transactions
        $recentTransactions = Transaction::query()
            ->with(['account', 'category', 'fromAccount', 'toAccount'])
            ->where('household_id', $hid)
            ->orderByDesc('occurred_at')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'monthLabel' => Carbon::now()->format('F Y'),
            'accountBalances' => $accountBalances,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'monthlyNet' => $monthlyNet,
            'topExpenseCategories' => $topExpenseCategories,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}

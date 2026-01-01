<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $month = $request->query('month', Carbon::now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) === 1, 422);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $income = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('household_id', $hid)
            ->where('type', 'income')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $expense = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('household_id', $hid)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $net = $income - $expense;

        $expenseByCategory = DB::table('transactions')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.household_id', $hid)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.occurred_at', [$start, $end])
            ->groupBy('transactions.category_id', 'categories.name')
            ->selectRaw('categories.name as category_name, SUM(transactions.amount) as total')
            ->orderByDesc('total')
            ->get();

        $incomeByCategory = DB::table('transactions')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.household_id', $hid)
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.occurred_at', [$start, $end])
            ->groupBy('transactions.category_id', 'categories.name')
            ->selectRaw('categories.name as category_name, SUM(transactions.amount) as total')
            ->orderByDesc('total')
            ->get();

        return view('reports.monthly', compact(
            'month',
            'income',
            'expense',
            'net',
            'expenseByCategory',
            'incomeByCategory'
        ));
    }
}

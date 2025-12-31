<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    protected $fillable = [
        'household_id',
        'name',
        'type',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function currentBalance(): int
    {
        $id = $this->id;

        $incomeExpense = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('household_id', $this->household_id)
            ->whereIn('type', ['income', 'expense'])
            ->where('account_id', $id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE -amount END),0) as bal")
            ->value('bal');

        $transferOut = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('household_id', $this->household_id)
            ->where('type', 'transfer')
            ->where('from_account_id', $id)
            ->sum('amount');

        $transferIn = (int) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('household_id', $this->household_id)
            ->where('type', 'transfer')
            ->where('to_account_id', $id)
            ->sum('amount');

        return $incomeExpense - $transferOut + $transferIn;
    }
}

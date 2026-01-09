<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionImport extends Model
{
    protected $fillable = [
        'household_id',
        'account_id',
        'created_by',
        'original_filename',
        'status',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(TransactionImportRow::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

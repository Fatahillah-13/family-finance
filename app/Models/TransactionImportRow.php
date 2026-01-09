<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionImportRow extends Model
{
    protected $fillable = [
        'transaction_import_id',
        'occurred_date',
        'description',
        'amount',
        'type',
        'hash',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'occurred_date' => 'date',
            'amount' => 'integer',
            'raw' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(TransactionImport::class, 'transaction_import_id');
    }
}

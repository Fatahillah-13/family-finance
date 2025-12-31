<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('attachments') && !Schema::hasTable('transaction_attachments')) {
            Schema::rename('attachments', 'transaction_attachments');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transaction_attachments') && !Schema::hasTable('attachments')) {
            Schema::rename('transaction_attachments', 'attachments');
        }
    }
};

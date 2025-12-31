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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('household_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // income|expense|transfer
            $table->dateTime('occurred_at'); // tanggal + jam

            $table->text('description')->nullable();

            // Store as integer Rupiah (no decimals)
            $table->bigInteger('amount');

            // income/expense
            $table->foreignId('account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();

            // transfer
            $table->foreignId('from_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->restrictOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'occurred_at']);
            $table->index(['household_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

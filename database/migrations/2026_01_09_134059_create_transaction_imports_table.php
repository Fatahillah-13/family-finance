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
        Schema::create('transaction_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('original_filename')->nullable();
            $table->string('status')->default('draft'); // draft|committed
            $table->timestamps();

            $table->index(['household_id', 'status']);
        });

        Schema::create('transaction_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_import_id')->constrained('transaction_imports')->cascadeOnDelete();

            $table->date('occurred_date');
            $table->text('description')->nullable();
            $table->bigInteger('amount'); // always positive integer
            $table->string('type')->nullable(); // income|expense (nullable until inferred)

            // dedupe key for this row (account + date + amount + description)
            $table->string('hash', 64);

            // original raw values (optional for debugging)
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['transaction_import_id']);
            $table->index(['hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_import_rows');
        Schema::dropIfExists('transaction_imports');
    }
};

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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();

            // Format: YYYY-MM
            $table->string('month', 7);

            // integer Rupiah
            $table->bigInteger('amount');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['household_id', 'category_id', 'month']);
            $table->index(['household_id', 'month', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

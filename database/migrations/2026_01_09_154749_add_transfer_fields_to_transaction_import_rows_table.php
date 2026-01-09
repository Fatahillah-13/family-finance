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
        Schema::table('transaction_import_rows', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_import_rows', 'from_account_id')) {
                $table->foreignId('from_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('type');
            }
            if (!Schema::hasColumn('transaction_import_rows', 'to_account_id')) {
                $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('from_account_id');
            }
            if (!Schema::hasColumn('transaction_import_rows', 'error')) {
                $table->string('error')->nullable()->after('hash');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_import_rows', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_import_rows', 'error')) {
                $table->dropColumn('error');
            }
            if (Schema::hasColumn('transaction_import_rows', 'to_account_id')) {
                $table->dropConstrainedForeignId('to_account_id');
            }
            if (Schema::hasColumn('transaction_import_rows', 'from_account_id')) {
                $table->dropConstrainedForeignId('from_account_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('subscriptions', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('amount_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }

            if (Schema::hasColumn('subscriptions', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
        });
    }
};

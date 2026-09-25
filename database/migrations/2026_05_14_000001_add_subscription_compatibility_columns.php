<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'duration')) {
                $table->integer('duration')->nullable()->after('price');
            }

            if (!Schema::hasColumn('plans', 'duration_type')) {
                $table->string('duration_type')->default('days')->after('duration');
            }

            if (!Schema::hasColumn('plans', 'status')) {
                $table->boolean('status')->default(true)->after('is_active');
            }
        });

        if (Schema::hasColumn('plans', 'duration_days')) {
            DB::table('plans')->whereNull('duration')->update([
                'duration' => DB::raw('duration_days'),
                'duration_type' => 'days',
            ]);
        }

        if (Schema::hasColumn('plans', 'is_active') && Schema::hasColumn('plans', 'status')) {
            DB::table('plans')->update([
                'status' => DB::raw('is_active'),
            ]);
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'payment_status')) {
                $table->boolean('payment_status')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        // These columns may already exist in imported databases, so do not drop them on rollback.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->json('format_prices')->nullable();
            $t->string('price_snapshot_source')->nullable();
        });
        Schema::table('trial_enrollments', fn (Blueprint $t) => $t->json('format_prices')->nullable());
        Schema::create('subscription_upgrades', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('subscription_id')->unique();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('plan_id');
            $t->string('from_type');
            $t->string('to_type')->default('both');
            $t->unsignedBigInteger('amount_minor');
            $t->string('currency', 3);
            $t->json('format_prices');
            $t->json('delivery_address');
            $t->dateTime('quoted_at');
            $t->dateTime('subscription_start');
            $t->dateTime('subscription_end');
            $t->string('payment_flow');
            $t->string('status')->default('pending');
            $t->string('payment_intent_id')->nullable()->unique();
            $t->string('checkout_session_id')->nullable()->unique();
            $t->unsignedInteger('checkout_attempt')->default(1);
            $t->dateTime('paid_at')->nullable();
            $t->dateTime('applied_at')->nullable();
            $t->timestamps();
        });
        // Historical combined prices were not stored. Freeze today's target
        // prices and use the actual package payment as the source baseline.
        DB::table('subscriptions')->orderBy('id')->chunkById(200, function ($subscriptions) {
            foreach ($subscriptions as $s) {
                $plan = DB::table('plans')->where('id', $s->plan_id)->first();
                if (!$plan) continue;
                $prices = [];
                foreach (['digital', 'print', 'both'] as $type) {
                    if (($plan->{$type.'_price'} ?? null) !== null) $prices[$type] = $plan->{$type.'_price'};
                }
                if ($s->amount_paid !== null) $prices[$s->delivery_type ?? 'digital'] = number_format(max(0, (float) $s->amount_paid - (float) ($s->support_amount ?? 0)), 2, '.', '');
                DB::table('subscriptions')->where('id', $s->id)->update(['format_prices' => json_encode($prices), 'price_snapshot_source' => 'legacy_current_prices']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_upgrades');
        Schema::table('trial_enrollments', fn (Blueprint $t) => $t->dropColumn('format_prices'));
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn(['format_prices', 'price_snapshot_source']));
    }
};

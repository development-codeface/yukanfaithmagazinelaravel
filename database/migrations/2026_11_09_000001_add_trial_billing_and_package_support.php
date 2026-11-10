<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            $t->decimal('support_amount', 10, 2)->default(0);
            $t->unsignedBigInteger('trial_conversion_plan_id')->nullable();
        });
        Schema::table('subscriptions', function (Blueprint $t) {
            $t->decimal('support_amount', 10, 2)->default(0);
            $t->string('currency', 3)->nullable();
        });
        Schema::create('trial_enrollments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->unique();
            $t->unsignedBigInteger('trial_plan_id');
            $t->unsignedBigInteger('annual_plan_id');
            $t->decimal('annual_amount', 10, 2);
            $t->string('currency', 3);
            $t->string('checkout_session_id')->nullable()->unique();
            $t->unsignedInteger('checkout_attempt')->default(1);
            $t->string('stripe_subscription_id')->nullable()->unique();
            $t->string('status')->default('pending');
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('cancel_requested')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_enrollments');
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn(['support_amount', 'currency']));
        Schema::table('plans', fn (Blueprint $t) => $t->dropColumn(['support_amount', 'trial_conversion_plan_id']));
    }
};

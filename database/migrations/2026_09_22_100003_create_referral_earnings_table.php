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
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_master_id')->constrained('masters');
            $table->foreignId('referred_master_id')->constrained('masters');
            $table->foreignId('referral_id')->constrained('referrals');
            $table->foreignId('payment_id')->constrained('payments');
            $table->unsignedBigInteger('payment_amount');
            $table->unsignedBigInteger('amount');
            $table->unsignedInteger('percent');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
    }
};

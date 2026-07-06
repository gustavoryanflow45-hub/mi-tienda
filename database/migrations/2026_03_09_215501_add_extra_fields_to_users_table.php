<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->default('default.png')->after('phone');
            $table->enum('user_type', ['customer', 'seller', 'admin'])->default('customer')->after('avatar');
            $table->tinyInteger('email_verified')->default(0)->after('user_type');
            $table->string('verification_code')->nullable()->after('email_verified');
            $table->decimal('balance', 10, 2)->default(0)->after('verification_code');
            $table->string('referral_code')->nullable()->unique()->after('balance');
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->tinyInteger('banned')->default(0)->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar', 'user_type', 'email_verified',
                'verification_code', 'balance', 'referral_code',
                'referred_by', 'banned'
            ]);
        });
    }
};
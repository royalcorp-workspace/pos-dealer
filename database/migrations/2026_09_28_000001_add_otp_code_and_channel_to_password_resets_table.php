<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            if (!Schema::hasColumn('password_resets', 'otp_code')) {
                $table->string('otp_code', 20)->nullable()->index();
            }
            if (!Schema::hasColumn('password_resets', 'channel')) {
                $table->string('channel', 20)->default('email')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            if (Schema::hasColumn('password_resets', 'otp_code')) {
                $table->dropColumn('otp_code');
            }
            if (Schema::hasColumn('password_resets', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private bool $createdTable = false;

    public function up(): void
    {
        if (! Schema::hasTable('device_sessions')) {
            $this->createdTable = true;
            Schema::create('device_sessions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable()->index();
                $table->string('user_email')->nullable()->index();
                $table->string('session_id')->nullable()->unique()->index();
                $table->uuid('refresh_token_id')->nullable()->index();
                $table->string('device_name');
                $table->string('device_type');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_active_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if ($this->createdTable) {
            Schema::dropIfExists('device_sessions');
        }
    }
};

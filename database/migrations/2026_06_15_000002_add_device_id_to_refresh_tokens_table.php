<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private bool $createdTable = false;

    public function up(): void
    {
        if (! Schema::hasTable('refresh_tokens')) {
            $this->createdTable = true;
            Schema::create('refresh_tokens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->index();
                $table->uuid('device_id')->nullable()->index();
                $table->string('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->boolean('revoked')->default(false);
                $table->text('device_info')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('refresh_tokens', 'device_id')) {
            Schema::table('refresh_tokens', function (Blueprint $table): void {
                $table->uuid('device_id')->nullable()->after('user_id')->index();
            });
        }
    }

    public function down(): void
    {
        if ($this->createdTable) {
            Schema::dropIfExists('refresh_tokens');
            return;
        }

        if (Schema::hasTable('refresh_tokens') && Schema::hasColumn('refresh_tokens', 'device_id')) {
            Schema::table('refresh_tokens', function (Blueprint $table): void {
                $table->dropIndex(['device_id']);
                $table->dropColumn('device_id');
            });
        }
    }
};

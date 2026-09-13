<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'platform']);

            $table->string('account_type', 40)->default('manual')->after('platform');
            $table->string('page_id')->nullable()->after('external_id');
            $table->string('page_name')->nullable()->after('page_id');
            $table->boolean('is_primary')->default(false)->after('metrics_json');

            $table->index(['user_id', 'platform', 'account_type']);
            $table->index(['user_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'platform', 'account_type']);
            $table->dropIndex(['user_id', 'external_id']);
            $table->dropColumn(['account_type', 'page_id', 'page_name', 'is_primary']);

            $table->unique(['user_id', 'platform']);
        });
    }
};

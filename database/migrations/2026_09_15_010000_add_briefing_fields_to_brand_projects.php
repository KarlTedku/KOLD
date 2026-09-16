<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_projects', function (Blueprint $table) {
            $table->string('campaign_objective')->nullable();
            $table->text('target_audience')->nullable();
            $table->json('collaboration_formats')->nullable();
            $table->string('compensation_type')->nullable();
            $table->string('usage_rights')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('brand_projects', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_objective',
                'target_audience',
                'collaboration_formats',
                'compensation_type',
                'usage_rights',
            ]);
        });
    }
};

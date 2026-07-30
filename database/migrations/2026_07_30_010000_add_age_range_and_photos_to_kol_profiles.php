<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->string('age_range', 20)->nullable()->after('languages');
            $table->json('photos')->nullable()->after('age_range');
        });
    }

    public function down(): void
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->dropColumn(['age_range', 'photos']);
        });
    }
};

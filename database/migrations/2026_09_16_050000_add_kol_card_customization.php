<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->string('card_accent', 24)->default('moss')->after('card_theme');
            $table->string('card_background_path')->nullable()->after('card_accent');
        });

        Schema::table('kol_card_links', function (Blueprint $table) {
            $table->string('icon', 40)->default('link')->after('type');
        });

        DB::table('kol_card_links')
            ->whereIn('type', ['instagram', 'youtube', 'tiktok', 'facebook', 'whatsapp'])
            ->update(['icon' => DB::raw('type')]);
    }

    public function down(): void
    {
        Schema::table('kol_card_links', function (Blueprint $table) {
            $table->dropColumn('icon');
        });

        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->dropColumn(['card_accent', 'card_background_path']);
        });
    }
};

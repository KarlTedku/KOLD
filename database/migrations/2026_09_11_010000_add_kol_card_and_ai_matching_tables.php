<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('display_name');
            $table->string('card_headline')->nullable()->after('bio');
            $table->string('external_contact_url')->nullable()->after('card_headline');
            $table->string('card_theme', 40)->default('classic')->after('external_contact_url');
        });

        Schema::create('kol_card_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kol_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('type', 40)->default('custom');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['kol_profile_id', 'is_active', 'sort_order']);
        });

        Schema::create('kol_ai_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kol_profile_id')->constrained()->cascadeOnDelete();
            $table->string('category', 60);
            $table->string('label');
            $table->string('status', 20)->default('suggested');
            $table->unsignedTinyInteger('confidence')->default(60);
            $table->text('rationale')->nullable();
            $table->string('source', 40)->default('ai');
            $table->timestamps();

            $table->index(['kol_profile_id', 'status']);
            $table->index(['category', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kol_ai_tags');
        Schema::dropIfExists('kol_card_links');

        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'card_headline', 'external_contact_url', 'card_theme']);
        });
    }
};

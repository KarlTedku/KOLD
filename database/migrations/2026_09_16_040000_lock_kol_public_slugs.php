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
            $table->timestamp('slug_locked_at')->nullable()->after('slug');
        });

        Schema::create('kol_profile_slug_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kol_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index('kol_profile_id');
        });

        Schema::create('kol_profile_slug_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kol_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('old_slug');
            $table->string('new_slug');
            $table->string('changed_by', 120);
            $table->text('reason');
            $table->timestamps();

            $table->index(['kol_profile_id', 'created_at']);
        });

        DB::table('kol_profiles')
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->update(['slug_locked_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kol_profile_slug_changes');
        Schema::dropIfExists('kol_profile_slug_aliases');

        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->dropColumn('slug_locked_at');
        });
    }
};

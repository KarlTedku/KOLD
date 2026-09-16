<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_source', 32)->nullable()->after('avatar');
        });

        DB::table('users')
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->update(['avatar_source' => 'oauth']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_source');
        });
    }
};

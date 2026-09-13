<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('brief');
            $table->json('niches')->nullable();
            $table->json('regions')->nullable();
            $table->json('platforms')->nullable();
            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();
            $table->text('deliverables')->nullable();
            $table->date('application_deadline')->nullable();
            $table->date('campaign_start_date')->nullable();
            $table->date('campaign_end_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['status', 'application_deadline']);
        });

        Schema::create('project_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kol_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('pitch');
            $table->unsignedInteger('proposed_rate')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('contact_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['brand_project_id', 'kol_user_id']);
            $table->index(['brand_project_id', 'status']);
        });

        Schema::create('collaborations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kol_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_application_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('contact_request_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status', 20)->default('negotiating');
            $table->timestamps();

            $table->index(['brand_user_id', 'status']);
            $table->index(['kol_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborations');
        Schema::dropIfExists('project_applications');
        Schema::dropIfExists('brand_projects');
    }
};

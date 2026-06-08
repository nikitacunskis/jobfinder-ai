<?php

use App\Enums\CompanyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('name')->unique();
            $table->text('industry')->default('');
            $table->string('status')->default(CompanyStatus::Spotted->value)->index();
            $table->timestampTz('status_updated_at')->useCurrent();
            $table->timestampsTz();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('name')->unique();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('skill_categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('category_key')->unique();
            $table->text('title')->default('');
            $table->timestampsTz();
        });

        Schema::create('user_skill_categories', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('skill_categories')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['user_id', 'category_id']);
        });

        Schema::create('skill_category_translations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('skill_categories')->cascadeOnDelete();
            $table->string('locale');
            $table->text('title');
            $table->timestampsTz();
            $table->unique(['user_id', 'category_id', 'locale']);
        });

        Schema::create('skill_category_skills', function (Blueprint $table) {
            $table->foreignUuid('category_id')->constrained('skill_categories')->cascadeOnDelete();
            $table->foreignUuid('skill_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['category_id', 'skill_id']);
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('job_hash')->unique();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->text('search_keyword')->default('');
            $table->text('language')->default('');
            $table->text('title')->default('');
            $table->text('link')->default('');
            $table->text('country')->default('');
            $table->text('city')->default('');
            $table->text('remote_type')->default('UNKNOWN');
            $table->text('origin_currency')->default('-NOT MENTIONED-');
            $table->text('salary_original')->default('-NOT MENTIONED-');
            $table->decimal('eur_month_min', 12, 2)->nullable();
            $table->decimal('eur_month_max', 12, 2)->nullable();
            $table->text('posted_date')->default('');
            $table->text('applicant_count')->default('');
            $table->boolean('easy_apply')->default(false);
            $table->text('employment_type')->default('');
            $table->text('poster_name')->default('');
            $table->text('poster_position')->default('');
            $table->text('poster_type')->default('UNKNOWN');
            $table->jsonb('all_skills')->default('[]');
            $table->jsonb('matching_skills')->default('[]');
            $table->jsonb('missing_skills')->default('[]');
            $table->text('raw_job_description')->default('');
            $table->jsonb('raw_payload')->default('{}');
            $table->timestampsTz();
            $table->index('company_id');
        });

        Schema::create('contact_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->text('contact_type')->default('note');
            $table->timestampTz('contact_at')->useCurrent();
            $table->text('subject')->default('');
            $table->text('message');
            $table->timestampTz('created_at')->useCurrent();
            $table->index('company_id');
            $table->index('job_id');
        });

        Schema::create('offer_records', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->string('status');
            $table->text('amount_money')->default('');
            $table->text('documents')->default('');
            $table->text('notes')->default('');
            $table->timestampTz('declined_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index('company_id');
            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_records');
        Schema::dropIfExists('contact_logs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('skill_category_skills');
        Schema::dropIfExists('skill_category_translations');
        Schema::dropIfExists('user_skill_categories');
        Schema::dropIfExists('skill_categories');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('companies');
    }
};

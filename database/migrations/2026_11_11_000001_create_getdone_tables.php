<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        // Namespace khusus agar tidak mengubah tabel Supabase existing (auth.users, public.users, dll).
        Schema::create('getdone_users', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('full_name', 160);
            $t->string('email')->unique();
            $t->string('university', 200)->nullable();
            $t->timestampTz('email_verified_at')->nullable();
            $t->string('password');
            $t->jsonb('onboarding_data')->nullable();
            $t->rememberToken();
            $t->timestampsTz();
        });
        Schema::create('getdone_courses', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('user_id')->constrained('getdone_users')->cascadeOnDelete();
            $t->string('code', 32); $t->string('title', 200);
            $t->unsignedTinyInteger('credits');
            $t->text('description')->nullable();
            $t->string('lecturer',200)->nullable();
            $t->string('schedule_day',30)->nullable(); $t->string('schedule_time',100)->nullable();
            $t->string('room',150)->nullable(); $t->text('image_url')->nullable(); $t->string('accent',100)->nullable();
            $t->unsignedTinyInteger('progress')->default(0);
            $t->string('semester_status',20)->default('current');
            $t->unsignedSmallInteger('semester_number')->nullable(); $t->string('result_status',20)->nullable();
            $t->timestampsTz(); $t->index(['user_id','semester_status']);
        });
        Schema::create('getdone_tasks', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('user_id')->constrained('getdone_users')->cascadeOnDelete();
            $t->foreignUuid('course_id')->nullable()->constrained('getdone_courses')->nullOnDelete();
            $t->string('title'); $t->text('description')->nullable(); $t->timestampTz('due_date');
            $t->string('priority', 10)->default('medium'); $t->string('status',20)->default('todo');
            $t->timestampTz('completed_at')->nullable();
            $t->timestampsTz(); $t->index(['user_id','due_date']);
        });
        Schema::create('getdone_focus_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('user_id')->constrained('getdone_users')->cascadeOnDelete();
            $t->foreignUuid('task_id')->nullable()->constrained('getdone_tasks')->nullOnDelete();
            $t->string('task_title'); $t->unsignedSmallInteger('duration_minutes');
            $t->timestampTz('completed_at'); $t->timestampsTz();
            $t->index(['user_id','completed_at']);
        });
        Schema::create('getdone_personal_access_tokens', function (Blueprint $t) {
            $t->id(); $t->uuidMorphs('tokenable');
            $t->string('name'); $t->string('token', 64)->unique();
            $t->text('abilities')->nullable(); $t->timestampTz('last_used_at')->nullable();
            $t->timestampTz('expires_at')->nullable(); $t->timestampsTz();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('getdone_personal_access_tokens');
        Schema::dropIfExists('getdone_focus_sessions');
        Schema::dropIfExists('getdone_tasks');
        Schema::dropIfExists('getdone_courses');
        Schema::dropIfExists('getdone_users');
    }
};

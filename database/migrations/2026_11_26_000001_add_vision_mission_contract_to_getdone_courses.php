<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('getdone_courses', function (Blueprint $t) {
            $t->text('vision')->nullable();
            $t->jsonb('mission')->nullable();
            // Kontrak kuliah (opsional). contract_path hanya dipakai server; tidak pernah dikirim ke klien.
            $t->string('contract_path')->nullable();
            $t->string('contract_name')->nullable();
            $t->unsignedInteger('contract_size')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('getdone_courses', function (Blueprint $t) {
            $t->dropColumn(['vision', 'mission', 'contract_path', 'contract_name', 'contract_size']);
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'is_active')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'name')) {
                $table->string('name', 100)->unique();
            }

            if (!Schema::hasColumn('permissions', 'module')) {
                $table->string('module', 100);
            }

            if (!Schema::hasColumn('permissions', 'action')) {
                $table->string('action', 50);
            }
        });

        Schema::table('permission_role', function (Blueprint $table) {
            if (!Schema::hasColumn('permission_role', 'role_id')) {
                $table->foreignId('role_id')
                    ->constrained('roles')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('permission_role', 'permission_id')) {
                $table->foreignId('permission_id')
                    ->constrained('permissions')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Existing schema is intentionally preserved.
    }
};

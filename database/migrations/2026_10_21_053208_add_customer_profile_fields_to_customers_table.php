<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person', 150)->nullable();
            }

            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city', 120)->nullable();
            }

            if (!Schema::hasColumn('customers', 'postal_code')) {
                $table->string('postal_code', 30)->nullable();
            }

            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country', 120)->nullable();
            }

            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = ['contact_person', 'city', 'postal_code', 'country', 'notes'];

        foreach ($columns as $column) {
            if (Schema::hasColumn('customers', $column)) {
                Schema::table('customers', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

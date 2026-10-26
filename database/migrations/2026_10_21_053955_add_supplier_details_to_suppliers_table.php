<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contact_person', 150)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country', 120)->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'contact_person',
                'city',
                'postal_code',
                'country',
                'notes',
            ]);
        });
    }
};

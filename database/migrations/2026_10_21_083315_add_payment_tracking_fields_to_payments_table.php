<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_number', 50)
                ->nullable()
                ->unique();

            $table->string('status', 20)
                ->default('completed')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['payment_number']);
            $table->dropIndex(['status']);
            $table->dropColumn(['payment_number', 'status']);
        });
    }
};
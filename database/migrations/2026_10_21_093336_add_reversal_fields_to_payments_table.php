<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('reversal_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn([
                'reversed_at',
                'reversal_reason',
            ]);
        });
    }
};
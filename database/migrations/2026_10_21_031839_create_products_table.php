<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('sku', 100)->unique();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->decimal('cost_price', 15, 2);
            $table->decimal('selling_price', 15, 2);

            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('name');
            $table->index(['is_active', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

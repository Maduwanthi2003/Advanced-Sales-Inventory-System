<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Cast prices and inventory values to their expected types.
    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'supplier_id',
        'cost_price',
        'selling_price',
        'quantity',
        'reorder_level',
        'description',
        'is_active',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Resolve the product's category.
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Resolve the product's supplier.
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Retrieve purchase line items for this product.
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Retrieve invoice line items for this product.
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Retrieve stock history for this product.
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}

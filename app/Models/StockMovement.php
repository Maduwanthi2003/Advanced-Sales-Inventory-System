<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    // Cast stock quantities to integers.
    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    // Resolve the product and user associated with this movement.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Resolve the model referenced by this movement.
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

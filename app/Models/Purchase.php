<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    // Cast the purchase date and total to their application types.
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'user_id',
        'purchase_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    // Resolve the supplier and user associated with this purchase.
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Retrieve the line items belonging to this purchase.
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}

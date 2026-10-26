<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'payment_status',
        'notes',
        'status',
'cancelled_at',
    ];

    // Cast invoice dates, totals, and cancellation time to their application types.
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    // Resolve the customer and user associated with this invoice.
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Retrieve the line items and payments belonging to this invoice.
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

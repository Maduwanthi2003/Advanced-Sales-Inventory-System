<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'payment_number',
        'invoice_id',
        'user_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'status',
        'reversed_at',
'reversed_by',
'reversal_reason',
    ];

    // Cast payment dates and amounts for consistent handling.
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    // Resolve the user who recorded the payment reversal.
    public function reversedBy()
{
    return $this->belongsTo(
        \App\Models\User::class,
        'reversed_by'
    );
}

    // Resolve the invoice and user associated with this payment.
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
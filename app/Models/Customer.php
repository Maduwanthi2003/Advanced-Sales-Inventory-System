<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    // Convert the active-status field to a boolean.
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'is_active',
        'address',
        'city',
        'postal_code',
        'country',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // A customer can have multiple invoices.
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}

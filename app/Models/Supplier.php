<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    // Convert the active-status field to a boolean.
    protected $fillable = [
        'name',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'postal_code',
        'country',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // A supplier can provide multiple products and purchases.
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}

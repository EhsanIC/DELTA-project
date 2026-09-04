<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'base_price',
    'unit_cost',
    'physical_inventory',
    'reserved_inventory',
    'safety_stock',
    'install_minutes_per_unit',
])]
class Product extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'physical_inventory' => 'integer',
            'reserved_inventory' => 'integer',
            'safety_stock' => 'integer',
            'install_minutes_per_unit' => 'integer',
        ];
    }
}

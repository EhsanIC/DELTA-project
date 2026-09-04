<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'product_id',
    'customer_id',
    'qty',
    'unit_price',
    'due_date',
    'stage',
])]
class Opportunity extends Model
{
    /**
     * Get the customer attached to the opportunity.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the product attached to the opportunity.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'customer_id' => 'integer',
            'qty' => 'integer',
            'unit_price' => 'decimal:2',
            'due_date' => 'date',
        ];
    }
}

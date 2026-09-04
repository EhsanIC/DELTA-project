<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date',
    'available_hours',
    'reason',
    'user_id',
])]
class CapacityAdjustment extends Model
{
    /**
     * Get the user who recorded the adjustment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'available_hours' => 'decimal:2',
            'user_id' => 'integer',
        ];
    }
}

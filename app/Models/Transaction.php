<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'transactionable_type',
        'transactionable_id',
        'type',
        'amount',
        'quantity',
        'note',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'meta' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}


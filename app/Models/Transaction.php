<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = [
        'store_id',
        'transactionable_type',
        'transactionable_id',
        'referenceable_type',
        'referenceable_id',
        'type',
        'amount',
        'note',
        'meta',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'balance' => PriceCast::class,
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

    public function referenceable(): MorphTo
    {
        return $this->morphTo('referenceable');
    }
}

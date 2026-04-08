<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleAuditLog extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'raffle_id',
        'executed_at',
        'executed_by',
        'algorithm_version',
        'seed_info',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleResult extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'raffle_id',
        'prize_id',
        'ticket_id',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class)->with('participant');
    }
}

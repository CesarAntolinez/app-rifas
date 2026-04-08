<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Raffle extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_CLOSED = 'closed';

    const STATUS_EXECUTED = 'executed';

    const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'image_path',
        'starts_at',
        'ends_at',
        'status',
        'ticket_digit_type',
        'series_count',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'series_count' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(RaffleResult::class);
    }

    public function auditLog(): HasOne
    {
        return $this->hasOne(RaffleAuditLog::class);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}

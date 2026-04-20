<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'type',
        'description',
        'cost',
        'technician',
        'completed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'completed_at' => 'date',
        ];
    }

    /**
     * The asset being maintained.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * The user who logged this maintenance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

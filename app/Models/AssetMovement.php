<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'from_location_id',
        'to_location_id',
        'user_id',
        'from_holder',
        'to_holder',
        'notes',
    ];

    /**
     * The asset being moved.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * The location where the asset came from.
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * The location where the asset is going to.
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * The user who recorded this movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

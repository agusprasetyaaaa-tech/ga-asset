<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\LogsActivity;

class Location extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Assets currently at this location.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * All movements TO this location.
     */
    public function incomingMovements(): HasMany
    {
        return $this->hasMany(AssetMovement::class, 'to_location_id');
    }

    /**
     * All movements FROM this location.
     */
    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(AssetMovement::class, 'from_location_id');
    }
}

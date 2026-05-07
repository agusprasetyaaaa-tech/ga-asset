<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_no',
        'location_id',
        'user_id',
        'status',
        'total_assets',
        'found_assets',
        'missing_assets',
        'mismatch_assets',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }

    public static function generateAuditNo()
    {
        $prefix = 'AUD-' . date('Ymd') . '-';
        $latest = self::where('audit_no', 'like', $prefix . '%')
            ->orderByDesc('audit_no')
            ->first();

        $number = 1;
        if ($latest) {
            $number = ((int) substr($latest->audit_no, -3)) + 1;
        }

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}

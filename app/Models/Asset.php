<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\LogsActivity;

class Asset extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'asset_code',
        'subcategory_id',
        'location_id',
        'name',
        'brand',
        'model_type',
        'serial_number',
        'license_key',
        'license_type',
        'license_expiration_date',
        'specification',
        'condition',
        'barcode_code',
        'description',
        'status',
        'department',
        'department_id',
        'vendor_id',
        'current_holder',
        'photo',
        'purchase_date',
        'purchase_price',
        'salvage_value',
        'useful_life',
        'vendor',
        'warranty_date',
        'usage_period',
        'notes',
        'asset_owner',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_date' => 'date',
            'purchase_price' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'useful_life' => 'integer',
            'license_expiration_date' => 'date',
        ];
    }

    /**
     * Calculate current book value using Straight-Line Depreciation
     */
    public function getCurrentBookValueAttribute(): float
    {
        if (!$this->purchase_price || !$this->purchase_date || $this->useful_life <= 0) {
            return (float) ($this->purchase_price ?? 0);
        }

        $purchaseDate = \Carbon\Carbon::parse($this->purchase_date);
        $now = now();
        
        // Calculate age in years (with decimal for precision)
        $ageInYears = $purchaseDate->diffInDays($now) / 365.25;

        if ($ageInYears <= 0) {
            return (float) $this->purchase_price;
        }

        if ($ageInYears >= $this->useful_life) {
            return (float) $this->salvage_value;
        }

        // Straight-Line Formula: 
        // Annual Depreciation = (Purchase Price - Salvage Value) / Useful Life
        $annualDepreciation = ($this->purchase_price - $this->salvage_value) / $this->useful_life;
        $accumulatedDepreciation = $annualDepreciation * $ageInYears;

        $bookValue = $this->purchase_price - $accumulatedDepreciation;

        return (float) max($bookValue, $this->salvage_value);
    }

    protected $appends = ['current_book_value'];

    /**
     * Generate a unique asset code using the concept:
     * [SubcategoryPrefix][Sequence]/[DepartmentCode]/[Month]/[Year]
     * Example: LAPTOP1/TEK/2/2026
     */
    public static function generateAssetCode(?int $subcategoryId = null, ?string $department = null, ?string $purchaseDate = null, bool $preview = false): string
    {
        try {
            // 1. Get Prefix from Subcategory
            $prefix = 'AST';
            if ($subcategoryId) {
                $sub = Subcategory::find($subcategoryId);
                if ($sub && $sub->prefix) {
                    $prefix = strtoupper($sub->prefix);
                }
            }

            // 2. Get Department Code
            $deptCode = 'DEPT';
            if ($department) {
                // If it's numeric, assume it's department_id
                if (is_numeric($department)) {
                    $deptObj = Department::find($department);
                    if ($deptObj) $deptCode = strtoupper($deptObj->code);
                } else {
                    $deptCode = strtoupper($department);
                }
            }

            // 3. Get Date Parts (Month & Year)
            $date = $purchaseDate ? \Carbon\Carbon::parse($purchaseDate) : now();
            $month = $date->format('n'); // 1-12
            $year = $date->format('Y');

            // 4. Calculate Sequence (Per Subcategory Prefix)
            // We search for the next number by looking at existing codes starting with this prefix
            $nextNumber = 1;
            
            // Search existing codes that start with the prefix followed by a number, then a slash
            $latestAsset = self::where('asset_code', 'like', $prefix . '%/%')
                ->get()
                ->filter(function($asset) use ($prefix) {
                    $parts = explode('/', $asset->asset_code);
                    $firstPart = $parts[0];
                    return str_starts_with($firstPart, $prefix) && is_numeric(substr($firstPart, strlen($prefix)));
                })
                ->map(function($asset) use ($prefix) {
                    $parts = explode('/', $asset->asset_code);
                    return (int) substr($parts[0], strlen($prefix));
                })
                ->max();

            if ($latestAsset) {
                $nextNumber = $latestAsset + 1;
            }

            $digitLength = AssetCodeSetting::getSetting()->digit_length ?? 1;
            $numberSegment = str_pad($nextNumber, $digitLength, '0', STR_PAD_LEFT);

            $code = "{$prefix}{$numberSegment}/{$deptCode}/{$month}/{$year}";

            // Ensure uniqueness (loop if needed, though rare with specific format)
            while (self::where('asset_code', $code)->exists()) {
                $nextNumber++;
                $numberSegment = str_pad($nextNumber, $digitLength, '0', STR_PAD_LEFT);
                $code = "{$prefix}{$numberSegment}/{$deptCode}/{$month}/{$year}";
            }

            return $code;
        } catch (\Exception $e) {
            return self::generateFallbackCode();
        }
    }

    private static function generateFallbackCode(): string
    {
        $prefix = 'AST-' . now()->format('Ym') . '-';
        $latest = self::where('asset_code', 'like', $prefix . '%')
            ->orderByDesc('asset_code')
            ->value('asset_code');
        $nextNumber = $latest ? ((int) substr($latest, -5)) + 1 : 1;
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public static function generateBarcodeCode(): string
    {
        return self::generateAssetCode();
    }

    /**
     * Set up relations
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class)->with(['fromLocation', 'toLocation', 'user'])->latest();
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class)->with('user')->latest();
    }

    public function department_rel(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function vendor_rel(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(AssetLoan::class)->orderByDesc('loan_date');
    }

    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }
}

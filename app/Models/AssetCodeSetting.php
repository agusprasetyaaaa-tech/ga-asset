<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCodeSetting extends Model
{
    protected $fillable = [
        'prefix',
        'separator',
        'date_format',
        'date_position',
        'digit_length',
        'reset_period',
        'next_number',
        'last_reset_ref',
    ];

    public static function getSetting(): self
    {
        return self::first() ?? self::create([
            'prefix' => 'AST',
            'separator' => '-',
            'date_format' => 'Ym',
            'date_position' => 'middle',
            'digit_length' => 5,
            'reset_period' => 'monthly',
            'next_number' => 1,
        ]);
    }

    public function getCurrentResetRef(): ?string
    {
        return match ($this->reset_period) {
            'yearly' => now()->format('Y'),
            'monthly' => now()->format('Ym'),
            default => null,
        };
    }

    public function getDateSegment(): string
    {
        if ($this->date_format === 'none' || empty($this->date_format)) {
            return '';
        }

        return match ($this->date_format) {
            'Y' => now()->format('Y'),
            'Ym' => now()->format('Ym'),
            'Ymd' => now()->format('Ymd'),
            'ym' => now()->format('ym'),
            'ymd' => now()->format('ymd'),
            default => now()->format('Ym'),
        };
    }

    public function getPreview(int $sampleNumber = 1): string
    {
        $parts = [$this->prefix];
        $dateSegment = $this->getDateSegment();
        $numberSegment = str_pad($sampleNumber, $this->digit_length, '0', STR_PAD_LEFT);

        if ($this->date_position === 'end') {
            // Format: PREFIX / NUMBER / DATE
            $parts[] = $numberSegment;
            if ($dateSegment) $parts[] = $dateSegment;
        } else {
            // Format: PREFIX / DATE / NUMBER (Default)
            if ($dateSegment) $parts[] = $dateSegment;
            $parts[] = $numberSegment;
        }

        return implode($this->separator, $parts);
    }

    public function generateNextCode(): string
    {
        if ($this->reset_period !== 'never') {
            $currentRef = $this->getCurrentResetRef();
            if ($this->last_reset_ref !== $currentRef) {
                $this->next_number = 1;
                $this->last_reset_ref = $currentRef;
            }
        }

        $code = $this->getPreview($this->next_number);
        $this->next_number++;
        $this->save();

        return $code;
    }
}

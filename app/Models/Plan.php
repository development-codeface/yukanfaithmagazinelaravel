<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Plan extends Model
{
      use Notifiable, HasFactory;

    public $table = 'plans';
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'digital_price', 'print_price', 'both_price', 'is_trial',
        'print_enabled',
        'name',
        'price',
        'duration_days',
        'duration',
        'duration_type',
        'description',
        'features',
        'is_active',
        'status',
    ];

    protected $casts = [
        'digital_price' => 'decimal:2',
        'print_price' => 'decimal:2',
        'both_price' => 'decimal:2',
        'is_trial' => 'boolean',
        'print_enabled' => 'boolean',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    public function isTrial(): bool
    {
        return (bool) $this->is_trial || $this->name === 'Free Trial';
    }

    public function prices(): array
    {
        $prices = [];
        foreach (['digital', 'print', 'both'] as $type) {
            if ($this->getAttribute($type.'_price') !== null) {
                $prices[$type] = $this->getAttribute($type.'_price');
            }
        }
        // Compatibility for records created before format pricing was introduced.
        if (!$prices && !array_key_exists('digital_price', $this->getAttributes())) {
            $prices[$this->print_enabled ? 'both' : 'digital'] = $this->price;
        }
        return $prices;
    }

    public function endsAt(\Carbon\Carbon $start): \Carbon\Carbon
    {
        return match ($this->duration_type) {
            'years' => $start->copy()->addYearsNoOverflow((int) $this->duration),
            'months' => $start->copy()->addMonthsNoOverflow((int) $this->duration),
            default => $start->copy()->addDays((int) ($this->duration ?? $this->duration_days ?? 7)),
        };
    }
}

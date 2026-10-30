<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelProductVariant extends Model
{
    protected $fillable = [
        'apparel_product_id',
        'option_type',
        'option_value',
        'color_name',
        'color_hex',
        'size',
        'sku',
        'price',
        'stock',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function selectionOptions(): array
    {
        $options = $this->optionValues->map(fn ($value) => [
            'key' => 'option-'.$value->apparel_product_option_id,
            'name' => $value->option->name,
            'type' => in_array(strtolower($value->option->type), ['color', 'colour']) || in_array(strtolower($value->option->name), ['color', 'colors', 'colour', 'colours']) ? 'color' : 'text',
            'value' => $value->value,
            'hex' => $value->color_hex,
        ])->values()->all();
        if (! $options) {
            if ($this->color_name) $options[] = ['key' => 'color', 'name' => 'Color', 'type' => 'color', 'value' => $this->color_name, 'hex' => $this->color_hex];
            if ($this->size) $options[] = ['key' => 'size', 'name' => 'Size', 'type' => 'text', 'value' => $this->size, 'hex' => null];
            if ($this->option_value) $options[] = ['key' => 'option', 'name' => 'Option', 'type' => 'text', 'value' => $this->option_value, 'hex' => null];
        }
        if (! $options) $options[] = ['key' => 'variant', 'name' => 'Option', 'type' => 'text', 'value' => $this->sku ?: 'Option '.$this->id, 'hex' => null];
        return $options;
    }

    public function colorSwatch(): ?array
    {
        $option = collect($this->selectionOptions())->firstWhere('type', 'color');
        if (! $option) return null;
        // Restrict inline CSS to actual hex colours or a small set of named colours.
        $hex = $option['hex'];
        $named = ['black' => '#000000', 'white' => '#ffffff', 'red' => '#dc143c', 'pink' => '#f4c7c7', 'blue' => '#2563eb', 'navy' => '#172554', 'green' => '#15803d', 'yellow' => '#facc15', 'gray' => '#9ca3af', 'grey' => '#9ca3af', 'brown' => '#92400e', 'orange' => '#f97316', 'purple' => '#9333ea', 'beige' => '#e8dcc4'];
        $option['hex'] = preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $hex ?? '') ? $hex : ($named[strtolower($option['value'])] ?? null);
        return $option;
    }

    public function product()
    {
        return $this->belongsTo(ApparelProduct::class, 'apparel_product_id');
    }

    public function images()
    {
        return $this->hasMany(ApparelProductImage::class)->orderBy('sort_order');
    }

    public function optionValues()
    {
        return $this->belongsToMany(
            ApparelProductOptionValue::class,
            'apparel_product_variant_values',
            'apparel_product_variant_id',
            'apparel_product_option_value_id'
        )->with('option')->withTimestamps();
    }
}

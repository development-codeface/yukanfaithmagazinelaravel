<?php

namespace App\Support;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrintDelivery
{
    public const FIELDS = ['name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'country'];

    public static function validate(Request $request, Plan $plan): array
    {
        if (!$plan->print_enabled) {
            return [];
        }

        return self::address($request->input('delivery_address'));
    }

    public static function address($address): array
    {
        $rules = ['delivery_address' => ['required', 'array']];
        foreach (self::FIELDS as $field) {
            $rules['delivery_address.'.$field] = [$field === 'line2' ? 'nullable' : 'required', 'string', 'max:200'];
        }
        $data = Validator::make(['delivery_address' => $address], $rules)->validate();

        return array_intersect_key($data['delivery_address'], array_flip(self::FIELDS));
    }

    public static function metadata(Request $request, Plan $plan): array
    {
        $metadata = [
            'metadata[print_enabled]' => $plan->print_enabled ? '1' : '0',
            'metadata[delivery_type]' => $plan->selected_delivery_type ?? ($plan->print_enabled ? 'both' : 'digital'),
        ];
        foreach (self::validate($request, $plan) as $field => $value) {
            $metadata['metadata[delivery_'.$field.']'] = $value ?? '';
        }
        return $metadata;
    }

    public static function fromMetadata(array $metadata): array
    {
        if (($metadata['print_enabled'] ?? '0') !== '1') {
            return [];
        }
        $address = [];
        foreach (self::FIELDS as $field) {
            $address[$field] = $metadata['delivery_'.$field] ?? null;
        }
        return self::address($address);
    }
}

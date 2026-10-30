<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApparelOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'number' => $this->number, 'status' => $this->status,
            'payment_status' => $this->payment_status, 'payment_intent_id' => $this->payment_intent_id,
            'total' => $this->total, 'currency' => $this->currency, 'paid_at' => $this->paid_at,
            'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone,
            'address' => $this->address, 'notes' => $this->notes, 'tracking_number' => $this->tracking_number,
            'items' => $this->whenLoaded('items'), 'created_at' => $this->created_at,
        ];
    }
}

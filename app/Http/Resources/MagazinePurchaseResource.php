<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MagazinePurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'magazine_issue_id' => $this->magazine_issue_id,
            'delivery_type' => $this->delivery_type,
            'amount_paid' => $this->amount_paid,
            'delivery_address' => $this->delivery_address,
            'print_acknowledged_at' => $this->print_acknowledged_at,
            'created_at' => $this->created_at,
            'magazine' => new MagazineIssueResource($this->whenLoaded('magazine')),
        ];
    }
}

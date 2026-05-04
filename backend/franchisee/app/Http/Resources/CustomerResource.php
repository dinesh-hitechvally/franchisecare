<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'other_email' => $this->other_email,
            'phone' => $this->phone,
            'other_phone' => $this->other_phone,
            'address' => $this->address,
            'street_address' => $this->street_address,
            'suburb' => $this->suburb,
            'postcode' => $this->postcode,
            'state' => $this->state,
            'franchise_id' => $this->franchise_id,
            'notes' => $this->notes,
            'referred_by' => $this->referred_by,
            'is_ndis' => $this->is_ndis,
            'is_subscribed' => $this->is_subscribed,
            'is_active' => $this->is_active,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'reference_id' => $this->reference_id,
            'is_archived' => $this->is_archived,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'pets' => PetResource::collection($this->whenLoaded('customerItems')),
        ];
    }
}

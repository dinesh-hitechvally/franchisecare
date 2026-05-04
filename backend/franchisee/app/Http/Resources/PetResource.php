<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PetResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'gender' => $this->gender,
            'birthDate' => $this->birth_date,
            'breed' => $this->breed,
            'size' => $this->size,
            'notes' => $this->notes,
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'isActive' => (bool) $this->is_active,
            'customerId' => $this->customer_id,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

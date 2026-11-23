<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Tenant
 */
class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_name' => $this->owner_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'gstin' => $this->gstin,
            'currency' => $this->currency,
            'status' => $this->status,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        ];
    }
}

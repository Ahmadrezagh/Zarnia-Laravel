<?php

namespace App\Http\Resources\Api\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if resource is null to prevent errors
        if (!$this->resource) {
            return [];
        }
        
        return [
            'name' => $this->name ?? null,
            'last_name' => $this->last_name ?? null,
            'phone' => $this->phone ?? null,
            'email' => $this->email ?? null,
            'orders' => $this->when($this->resource, fn() => $this->orders()->count(), 0),
            'addresses' => $this->when($this->resource, fn() => $this->addresses()->count(), 0),
        ];
    }
}

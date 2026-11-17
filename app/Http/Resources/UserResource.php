<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value ?? (string) $this->role,
            'role_label' => $this->role?->label(),
            'status' => $this->status?->value ?? (string) $this->status,
            'phone' => $this->phone,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}


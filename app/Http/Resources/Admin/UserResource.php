<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'is_active'  => (bool) $this->is_active,
            'avatar'     => $this->avatar,
            'birth_date' => optional($this->birth_date)->format('Y-m-d'),
            'phone'      => $this->phone,
            'address'    => $this->address,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
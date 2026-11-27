<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Admin */
class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status(), // active | invited | inactive
            'is_active' => $this->is_active,
            'is_current' => $request->user()?->is($this->resource) ?? false,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at ? \Illuminate\Support\Carbon::parse($this->created_at)->toIso8601String() : null,
        ];
    }
}

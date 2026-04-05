<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class PosOfflineQueueResource extends JsonResource
{
    public function toArray($request): array
    {
        $createdAt = $this->created_at;

        return [
            'id' => $this->id,
            'machine_id' => $this->machine_id,
            'status' => $this->status,
            'attempts' => (int) ($this->attempts ?? 0),
            'sale_data' => $this->sale_data,
            'error_message' => $this->error_message,
            'created_at' => $createdAt?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'age_minutes' => $createdAt ? now()->diffInMinutes($createdAt) : null,
        ];
    }
}

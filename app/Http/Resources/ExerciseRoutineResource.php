<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseRoutineResource extends JsonResource
{
    /**
     * Ejercicio con datos pivot al mismo nivel de jerarquía.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'instruction' => $this->instruction,
            'category_id' => $this->category_id,
            // Datos pivot al mismo nivel
            'sequence' => $this->pivot->sequence,
            'target_sets' => $this->pivot->target_sets,
            'target_reps' => $this->pivot->target_reps,
            'rest_seconds' => $this->pivot->rest_seconds,
        ];
    }
}

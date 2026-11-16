<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MantenimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipo_id' => $this->equipo_id,
            'equipo' => new EquipoResource($this->whenLoaded('equipo')),
            'tipo' => $this->tipo,
            'fecha_programada' => $this->fecha_programada->format('Y-m-d'),
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
        ];
    }
}

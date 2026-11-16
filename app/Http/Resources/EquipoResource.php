<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'tipo' => $this->tipo,
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado,
            'fecha_registro' => $this->fecha_registro->format('Y-m-d'),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EquipoResource;
use App\Models\Equipo;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EquipoResource::collection(Equipo::query()->latest('id')->paginate(10));
    }

    public function show(Equipo $equipo): EquipoResource
    {
        return new EquipoResource($equipo);
    }
}

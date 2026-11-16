<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MantenimientoResource;
use App\Models\Mantenimiento;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MantenimientoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MantenimientoResource::collection(Mantenimiento::query()->with('equipo')->latest('id')->paginate(10));
    }

    public function show(Mantenimiento $mantenimiento): MantenimientoResource
    {
        return new MantenimientoResource($mantenimiento->load('equipo'));
    }
}

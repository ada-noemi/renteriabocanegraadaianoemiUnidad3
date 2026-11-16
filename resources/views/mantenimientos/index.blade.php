@extends('layouts.app')

@section('title', 'Mantenimientos')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Consulta y administra los mantenimientos de los equipos.</p>
        @can('administrar')
        <button type="button" data-open-modal="mantenimiento-create" @disabled($equiposDisponibles->isEmpty())
                class="rounded-lg bg-teal-700 px-5 py-3 text-sm font-medium text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:bg-slate-400">Registrar mantenimiento</button>
        @endcan
    </div>
    @if (auth()->user()->can('administrar') && $equiposDisponibles->isEmpty())
        <div role="status" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Primero registra un equipo para poder agregar mantenimientos.
            <a href="{{ route('equipos.index') }}" class="font-medium underline">Ir a Equipos</a>
        </div>
    @endif
    @error('estado')
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @if (session('success'))
        <div role="status" class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('success') }}</div>
    @endif
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-lg font-semibold">Listado de mantenimientos</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $mantenimientos->total() }} mantenimientos registrados</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <caption class="sr-only">Listado de mantenimientos</caption>
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        @foreach (['Equipo', 'Tipo', 'Fecha programada', 'Estado', 'Acciones'] as $column)
                            <th scope="col" class="px-6 py-4 font-medium">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($mantenimientos as $mantenimiento)
                        <tr>
                            <td class="max-w-64 break-words px-6 py-4">
                                <p class="font-medium">{{ $mantenimiento->equipo->nombre }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $mantenimiento->equipo->codigo }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $mantenimiento->tipo }}</td>
                            <td class="whitespace-nowrap px-6 py-4">{{ $mantenimiento->fecha_programada->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">
                                <span @class(['inline-block whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium', 'bg-amber-50 text-amber-800' => $mantenimiento->estado === 'Pendiente', 'bg-blue-50 text-blue-800' => $mantenimiento->estado === 'En proceso', 'bg-teal-50 text-teal-800' => $mantenimiento->estado === 'Finalizado'])>{{ $mantenimiento->estado }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @can('administrar')
                                <div class="flex items-center gap-4">
                                    <button type="button" data-open-modal="mantenimiento-edit-{{ $mantenimiento->id }}" aria-label="Editar mantenimiento de {{ $mantenimiento->equipo->nombre }}" class="font-medium text-teal-700 hover:underline">Editar</button>
                                    <button type="button" data-open-modal="mantenimiento-delete-{{ $mantenimiento->id }}" aria-label="Eliminar mantenimiento de {{ $mantenimiento->equipo->nombre }}" class="font-medium text-red-700 hover:underline">Eliminar</button>
                                </div>
                                @elsecan('actualizar-estado')
                                    <form action="{{ route('mantenimientos.estado', $mantenimiento) }}" method="POST" class="flex flex-wrap gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label for="estado-{{ $mantenimiento->id }}" class="sr-only">Estado del mantenimiento de {{ $mantenimiento->equipo->nombre }}</label>
                                        <select id="estado-{{ $mantenimiento->id }}" name="estado" class="rounded-lg border border-slate-300 bg-white px-3 py-2">
                                            @foreach (['Pendiente', 'En proceso', 'Finalizado'] as $estado)
                                                <option @selected(old('estado', $mantenimiento->estado) === $estado)>{{ $estado }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-teal-700 px-3 py-2 text-white hover:bg-teal-800">Actualizar estado</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Todavía no existen registros de mantenimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($mantenimientos->hasPages())
            <div class="border-t border-slate-200 p-6">{{ $mantenimientos->links() }}</div>
        @endif
    </section>
    @can('administrar')
    <div class="contents">
        @include('mantenimientos.modal', ['mantenimiento' => new \App\Models\Mantenimiento])
        @foreach ($mantenimientos as $mantenimiento)
            @include('mantenimientos.modal', ['mantenimiento' => $mantenimiento])
            @include('mantenimientos.delete-modal', ['mantenimiento' => $mantenimiento])
        @endforeach
        @if ($mantenimientoModal && ! $mantenimientos->contains('id', $mantenimientoModal->id))
            @include('mantenimientos.modal', ['mantenimiento' => $mantenimientoModal])
        @endif
    </div>
    @endcan
@endsection

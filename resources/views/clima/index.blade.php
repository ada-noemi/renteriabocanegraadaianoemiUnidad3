@extends('layouts.app')

@section('title', 'Clima')

@section('content')
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Clima para mantenimientos en exteriores</h2>
        <p class="mt-2 text-sm text-slate-600">Consulta el pronóstico de los próximos tres días antes de programar un mantenimiento al aire libre.</p>
        <form action="{{ route('clima.index') }}" method="GET" class="mt-5 flex flex-wrap items-end gap-3">
            <div class="flex-1">
                <label for="ciudad" class="mb-2 block text-sm font-medium">Ciudad</label>
                <input id="ciudad" name="ciudad" value="{{ old('ciudad', $ciudad) }}" required minlength="3" maxlength="100"
                    placeholder="Ejemplo: Monterrey, México"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-200">
                @error('ciudad')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-lg bg-teal-700 px-5 py-3 text-sm font-medium text-white hover:bg-teal-800">Consultar clima</button>
        </form>
    </section>
    @if ($error)
        <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $error }}</div>
    @endif
    @if ($ubicacion && ! $error)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-200 p-6 text-lg font-semibold">{{ $ubicacion }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Pronóstico del clima</caption>
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th scope="col" class="px-6 py-4">Fecha local</th>
                            <th scope="col" class="px-6 py-4">Temperatura máxima</th>
                            <th scope="col" class="px-6 py-4">Probabilidad de lluvia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($pronostico as $dia)
                            <tr>
                                <td class="px-6 py-4">{{ $dia['fecha'] }}</td>
                                <td class="px-6 py-4">{{ $dia['temperatura'] }} °C</td>
                                <td class="px-6 py-4">{{ $dia['lluvia'] }} %</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    <p class="text-xs text-slate-500">Datos meteorológicos de <a href="https://open-meteo.com/" class="underline" target="_blank" rel="noopener noreferrer">Open-Meteo</a>; ubicaciones de GeoNames.</p>
@endsection

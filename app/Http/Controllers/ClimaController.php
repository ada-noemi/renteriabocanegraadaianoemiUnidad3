<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use UnexpectedValueException;

class ClimaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = $request->validate(['ciudad' => ['nullable', 'string', 'min:3', 'max:100']]);
        $ciudad = $data['ciudad'] ?? '';
        $ubicacion = null;
        $pronostico = [];
        $error = null;

        if ($ciudad !== '') {
            try {
                $result = Http::acceptJson()->connectTimeout(3)->timeout(8)
                    ->get('https://geocoding-api.open-meteo.com/v1/search', [
                        'name' => $ciudad, 'count' => 1, 'language' => 'es',
                    ])->throw()->json();
                $place = data_get($result, 'results.0');

                if (! $place) {
                    $error = 'No se encontró esa ciudad. Prueba con el nombre y el país.';
                } else {
                    if (! is_numeric(data_get($place, 'latitude')) || ! is_numeric(data_get($place, 'longitude')) || ! is_string(data_get($place, 'name'))) {
                        throw new UnexpectedValueException;
                    }

                    $weather = Http::acceptJson()->connectTimeout(3)->timeout(8)
                        ->get('https://api.open-meteo.com/v1/forecast', [
                            'latitude' => $place['latitude'], 'longitude' => $place['longitude'],
                            'daily' => 'temperature_2m_max,precipitation_probability_max',
                            'timezone' => 'auto', 'forecast_days' => 3,
                        ])->throw()->json();
                    $days = data_get($weather, 'daily.time');

                    if (! is_array($days) || count($days) !== 3) {
                        throw new UnexpectedValueException;
                    }

                    foreach ($days as $index => $day) {
                        $temperature = data_get($weather, 'daily.temperature_2m_max.'.$index);
                        $rain = data_get($weather, 'daily.precipitation_probability_max.'.$index);
                        if (! is_string($day) || ! is_numeric($temperature) || ! is_numeric($rain)) {
                            throw new UnexpectedValueException;
                        }
                        $pronostico[] = ['fecha' => $day, 'temperatura' => $temperature, 'lluvia' => $rain];
                    }
                    $ubicacion = implode(', ', array_filter([$place['name'], $place['admin1'] ?? null, $place['country'] ?? null]));
                }
            } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
                $pronostico = [];
                $error = 'El servicio del clima no está disponible. Intenta más tarde.';
            }
        }

        return view('clima.index', compact('ciudad', 'ubicacion', 'pronostico', 'error'));
    }
}

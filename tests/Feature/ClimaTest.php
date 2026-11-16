<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClimaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function lugar(): array
    {
        return ['results' => [['name' => 'Monterrey', 'country' => 'México', 'latitude' => 25.67, 'longitude' => -100.31]]];
    }

    public function test_visitante_no_consulta_y_pantalla_inicial_no_llama_servicio(): void
    {
        Http::fake();
        $this->get(route('clima.index', ['ciudad' => 'Monterrey']))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('clima.index'))->assertOk()->assertSee('Consultar clima');
        Http::assertNothingSent();
    }

    public function test_ambos_roles_consultan_pronostico_de_terceros(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response($this->lugar()),
            'api.open-meteo.com/*' => Http::response(['daily' => [
                'time' => ['2026-11-15', '2026-11-16', '2026-11-17'],
                'temperature_2m_max' => [27, 28, 26], 'precipitation_probability_max' => [10, 20, 30],
            ]]),
        ]);
        foreach ([User::ROLE_ADMIN, User::ROLE_TECNICO] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('clima.index', ['ciudad' => 'Monterrey']))
                ->assertOk()->assertSee('Monterrey, México')->assertSee('27 °C')->assertSee('30 %')->assertSee('2026-11-17');
        }
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://geocoding-api.open-meteo.com/') && $request['name'] === 'Monterrey');
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.open-meteo.com/') && $request['latitude'] == 25.67 && $request['forecast_days'] === 3);
    }

    public function test_ciudad_inexistente_no_solicita_pronostico(): void
    {
        Http::fake(['geocoding-api.open-meteo.com/*' => Http::response([])]);
        $this->actingAs(User::factory()->create())->get(route('clima.index', ['ciudad' => 'NoExiste']))
            ->assertOk()->assertSee('No se encontró esa ciudad.');
        Http::assertSentCount(1);
    }

    public function test_errores_del_proveedor_muestran_mensaje_sin_romper_pantalla(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([Http::response([], 503), Http::failedConnection(), Http::response(['daily' => []])] as $failure) {
            Http::fake([
                'geocoding-api.open-meteo.com/*' => Http::response($this->lugar()),
                'api.open-meteo.com/*' => $failure,
            ]);
            $this->get(route('clima.index', ['ciudad' => 'Monterrey']))
                ->assertOk()->assertSee('El servicio del clima no está disponible.')
                ->assertDontSee('Temperatura máxima');
        }
    }

    public function test_ciudad_invalida_no_llama_al_servicio(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create())->from(route('clima.index'))
            ->get(route('clima.index', ['ciudad' => str_repeat('a', 101)]))
            ->assertRedirect(route('clima.index'))->assertSessionHasErrors('ciudad');
        Http::assertNothingSent();
    }
}

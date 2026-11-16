<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiConsultaTest extends TestCase
{
    use RefreshDatabase;

    private function mantenimiento(string $codigo = 'API-001'): Mantenimiento
    {
        $equipo = Equipo::create([
            'nombre' => 'Computadora', 'codigo' => $codigo, 'tipo' => 'Computadora',
            'ubicacion' => 'Laboratorio', 'estado' => 'Activo', 'fecha_registro' => '2026-11-15',
        ]);

        return Mantenimiento::create([
            'equipo_id' => $equipo->id, 'tipo' => 'Preventivo', 'estado' => 'Pendiente',
            'fecha_programada' => '2026-11-20', 'descripcion' => 'Limpieza del equipo',
        ]);
    }

    public function test_ambos_roles_consultan_listados_y_detalles_con_token(): void
    {
        $mantenimiento = $this->mantenimiento();
        foreach ([User::ROLE_ADMIN, User::ROLE_TECNICO] as $role) {
            $this->app['auth']->forgetGuards();
            $user = User::factory()->create(['role' => $role]);
            $headers = ['Authorization' => 'Bearer '.$user->createToken('prueba', ['read'])->plainTextToken];
            $this->getJson(route('api.v1.equipos.index'), $headers)->assertOk()
                ->assertJsonPath('data.0.codigo', 'API-001')->assertJsonPath('meta.total', 1);
            $this->getJson(route('api.v1.equipos.show', $mantenimiento->equipo_id), $headers)->assertOk()
                ->assertJsonPath('data.fecha_registro', '2026-11-15')->assertJsonPath('data.nombre', 'Computadora');
            $this->getJson(route('api.v1.mantenimientos.index'), $headers)->assertOk()
                ->assertJsonPath('data.0.equipo.codigo', 'API-001')->assertJsonPath('data.0.estado', 'Pendiente');
            $this->getJson(route('api.v1.mantenimientos.show', $mantenimiento), $headers)->assertOk()
                ->assertJsonPath('data.descripcion', 'Limpieza del equipo')
                ->assertJsonPath('data.fecha_programada', '2026-11-20');
        }
    }

    public function test_consultas_sin_token_son_rechazadas(): void
    {
        $mantenimiento = $this->mantenimiento();
        foreach (['equipos' => $mantenimiento->equipo_id, 'mantenimientos' => $mantenimiento->id] as $resource => $id) {
            $this->getJson(route('api.v1.'.$resource.'.index'))->assertUnauthorized();
            $this->getJson(route('api.v1.'.$resource.'.show', $id))->assertUnauthorized();
        }
    }

    public function test_token_sin_permiso_de_lectura_no_accede(): void
    {
        $mantenimiento = $this->mantenimiento();
        $token = User::factory()->create()->createToken('sin lectura', [])->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];
        foreach (['equipos' => $mantenimiento->equipo_id, 'mantenimientos' => $mantenimiento->id] as $resource => $id) {
            $this->getJson(route('api.v1.'.$resource.'.index'), $headers)->assertForbidden();
            $this->getJson(route('api.v1.'.$resource.'.show', $id), $headers)->assertForbidden();
        }
    }

    public function test_listados_vacios_y_registros_inexistentes(): void
    {
        $token = User::factory()->create()->createToken('lectura', ['read'])->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];
        foreach (['equipos', 'mantenimientos'] as $resource) {
            $this->getJson(route('api.v1.'.$resource.'.index'), $headers)->assertOk()
                ->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
            $this->getJson(route('api.v1.'.$resource.'.show', 999), $headers)->assertNotFound();
        }
    }

    public function test_listados_paginados_no_omiten_registros(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->mantenimiento('API-'.$i);
        }
        $token = User::factory()->create()->createToken('lectura', ['read'])->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];
        foreach (['equipos', 'mantenimientos'] as $resource) {
            $this->getJson(route('api.v1.'.$resource.'.index'), $headers)->assertOk()
                ->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 11)->assertJsonPath('data.0.id', 11);
            $this->getJson(route('api.v1.'.$resource.'.index', ['page' => 2]), $headers)->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 1);
        }
    }
}

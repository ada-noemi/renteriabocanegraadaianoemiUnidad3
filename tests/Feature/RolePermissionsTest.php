<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function mantenimiento(): Mantenimiento
    {
        $equipo = Equipo::create([
            'nombre' => 'Equipo prueba', 'codigo' => 'ROL-001', 'tipo' => 'Computadora',
            'ubicacion' => 'Laboratorio', 'estado' => 'Activo', 'fecha_registro' => '2026-11-15',
        ]);

        return Mantenimiento::create([
            'equipo_id' => $equipo->id, 'tipo' => 'Preventivo', 'fecha_programada' => '2026-11-20',
            'descripcion' => 'Revision original', 'estado' => 'Pendiente',
        ]);
    }

    public function test_tecnico_consulta_sin_controles_de_administracion(): void
    {
        $this->mantenimiento();
        $this->actingAs(User::factory()->create());
        $this->get(route('equipos.index'))->assertOk()->assertSee('Equipo prueba')
            ->assertDontSee('Registrar equipo')->assertDontSee('data-open-modal="equipo-edit-', false);
        $this->get(route('mantenimientos.index'))->assertOk()->assertSee('Actualizar estado')
            ->assertDontSee('Registrar mantenimiento')->assertDontSee('data-open-modal="mantenimiento-delete-', false);
    }

    public function test_tecnico_no_puede_administrar_mediante_peticiones_directas(): void
    {
        $mantenimiento = $this->mantenimiento();
        $equipo = $mantenimiento->equipo;
        $this->actingAs(User::factory()->create());

        foreach (['equipos' => $equipo, 'mantenimientos' => $mantenimiento] as $resource => $model) {
            $this->get(route($resource.'.create'))->assertForbidden();
            $this->get(route($resource.'.edit', $model))->assertForbidden();
            $this->post(route($resource.'.store'), [])->assertForbidden();
            $this->put(route($resource.'.update', $model), [])->assertForbidden();
            $this->patch(route($resource.'.update', $model), [])->assertForbidden();
            $this->delete(route($resource.'.destroy', $model))->assertForbidden();
        }

        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseCount('mantenimientos', 1);
        $this->assertSame('Revision original', $mantenimiento->fresh()->descripcion);
    }

    public function test_tecnico_actualiza_solo_estado_aunque_envie_campos_adicionales(): void
    {
        $mantenimiento = $this->mantenimiento();
        $this->actingAs(User::factory()->create())
            ->patch(route('mantenimientos.estado', $mantenimiento), [
                'estado' => 'Finalizado', 'descripcion' => 'Cambio prohibido',
                'tipo' => 'Correctivo', 'equipo_id' => 999, 'fecha_programada' => '2027-01-01',
            ])->assertRedirect(route('mantenimientos.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mantenimientos', [
            'id' => $mantenimiento->id, 'estado' => 'Finalizado',
            'descripcion' => 'Revision original', 'tipo' => 'Preventivo',
            'equipo_id' => $mantenimiento->equipo_id,
        ]);
        $this->assertSame('2026-11-20', $mantenimiento->fresh()->fecha_programada->format('Y-m-d'));
    }

    public function test_estado_invalido_o_ausente_no_modifica_mantenimiento(): void
    {
        $mantenimiento = $this->mantenimiento();
        $this->actingAs(User::factory()->create());
        foreach ([[], ['estado' => 'Inventado']] as $payload) {
            $this->from(route('mantenimientos.index'))
                ->patch(route('mantenimientos.estado', $mantenimiento), $payload)
                ->assertRedirect(route('mantenimientos.index'))->assertSessionHasErrors('estado');
        }
        $this->assertSame('Pendiente', $mantenimiento->fresh()->estado);
    }

    public function test_visitante_no_actualiza_estado(): void
    {
        $mantenimiento = $this->mantenimiento();
        $this->patch(route('mantenimientos.estado', $mantenimiento), ['estado' => 'Finalizado'])
            ->assertRedirect(route('login'));
        $this->assertSame('Pendiente', $mantenimiento->fresh()->estado);
    }

    public function test_registro_ignora_rol_administrador_enviado_por_cliente(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Tecnico nuevo', 'email' => 'tecnico@example.com',
            'password' => 'Clave123!', 'password_confirmation' => 'Clave123!',
            'role' => User::ROLE_ADMIN,
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $user = User::where('email', 'tecnico@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_TECNICO, $user->role);
        $this->assertFalse($user->can('administrar'));
    }

    public function test_permisos_por_rol_y_comando_controlado(): void
    {
        $tecnico = User::factory()->create();
        $admin = User::factory()->administrador()->create();
        $this->assertFalse($tecnico->can('administrar'));
        $this->assertTrue($tecnico->can('actualizar-estado'));
        $this->assertTrue($admin->can('administrar'));
        $this->assertTrue($admin->can('actualizar-estado'));

        $this->artisan('users:make-admin', ['email' => $tecnico->email])->assertSuccessful();
        $this->assertTrue($tecnico->fresh()->isAdmin());
        $this->artisan('users:make-admin', ['email' => 'inexistente@example.com'])->assertFailed();
    }

    public function test_administrador_ve_controles_y_actualiza_estado(): void
    {
        $mantenimiento = $this->mantenimiento();
        $this->actingAs(User::factory()->administrador()->create());
        $this->get(route('equipos.index'))->assertOk()->assertSee('Registrar equipo');
        $this->get(route('mantenimientos.index'))->assertOk()->assertSee('Registrar mantenimiento');
        $this->patch(route('mantenimientos.estado', $mantenimiento), ['estado' => 'En proceso'])
            ->assertRedirect(route('mantenimientos.index'));
        $this->assertSame('En proceso', $mantenimiento->fresh()->estado);
    }
}

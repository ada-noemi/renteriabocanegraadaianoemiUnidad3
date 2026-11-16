<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $changes = []): array
    {
        return array_replace([
            'name' => 'Cuenta nueva', 'email' => 'cuenta@example.com', 'role' => User::ROLE_TECNICO,
            'password' => 'Clave123!', 'password_confirmation' => 'Clave123!',
        ], $changes);
    }

    public function test_administrador_crea_ambos_roles_sin_cambiar_su_sesion(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin);
        $this->get(route('usuarios.index'))->assertOk()->assertSee('Crear cuenta')->assertSee('Cuentas registradas');
        foreach ([User::ROLE_ADMIN, User::ROLE_TECNICO] as $role) {
            $email = $role.'@example.com';
            $this->post(route('usuarios.store'), $this->datos(['email' => $email, 'role' => $role]))
                ->assertRedirect(route('usuarios.index'))->assertSessionHasNoErrors();
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertTrue(Hash::check('Clave123!', $user->password));
            $this->assertAuthenticatedAs($admin);
        }
        $this->get(route('usuarios.index'))->assertSee('administrador@example.com')->assertSee('tecnico@example.com');
    }

    public function test_tecnico_no_ve_menu_ni_puede_consultar_o_crear_cuentas(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('dashboard'))->assertOk()->assertDontSee(route('usuarios.index'));
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->post(route('usuarios.store'), $this->datos(['role' => User::ROLE_ADMIN]))->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_visitante_es_redirigido_al_login(): void
    {
        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
        $this->post(route('usuarios.store'), $this->datos())->assertRedirect(route('login'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_datos_invalidos_no_crean_usuarios_y_contrasena_no_se_conserva(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin);
        $this->post(route('usuarios.store'), [])->assertRedirect(route('usuarios.index'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
        $this->post(route('usuarios.store'), $this->datos([
            'email' => $admin->email, 'role' => 'superadmin', 'password_confirmation' => 'OtraClave',
        ]))->assertRedirect(route('usuarios.index'))->assertSessionHasErrors(['email', 'role', 'password'])
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->assertDatabaseCount('users', 1);
        $this->get(route('usuarios.index'))->assertOk()->assertSee('Este correo electrónico ya está registrado.');
    }
}

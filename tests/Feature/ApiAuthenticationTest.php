<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_emite_token_con_vencimiento_y_sin_datos_secretos(): void
    {
        $user = User::factory()->create(['password' => 'Clave123!']);
        $response = $this->postJson(route('api.v1.login'), ['email' => $user->email, 'password' => 'Clave123!'])
            ->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('user.role', User::ROLE_TECNICO)
            ->assertJsonMissingPath('user.password')->assertJsonMissingPath('user.remember_token');
        $token = $user->tokens()->firstOrFail();
        $this->assertNotSame($response->json('access_token'), $token->token);
        $this->assertTrue($token->expires_at->isFuture());
        $this->assertTrue($token->expires_at->lessThanOrEqualTo(now()->addDay()));
        $this->getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.$response->json('access_token')])
            ->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonMissingPath('data.password');
        $this->assertGuest('web');
    }

    public function test_credenciales_incorrectas_no_emiten_token(): void
    {
        $user = User::factory()->create();
        foreach ([$user->email, 'inexistente@example.com'] as $email) {
            $this->postJson(route('api.v1.login'), ['email' => $email, 'password' => 'Incorrecta'])
                ->assertUnauthorized()->assertJsonPath('message', 'El correo electrónico o la contraseña son incorrectos.');
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_valida_datos_y_responde_json_sin_header_accept(): void
    {
        $this->post(route('api.v1.login'), [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson(route('api.v1.login'), ['email' => 'invalido', 'password' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_rechaza_tokens_ausentes_invalidos_y_expirados(): void
    {
        $user = User::factory()->create();
        $expired = $user->createToken('expirado', ['read'], now()->subMinute())->plainTextToken;
        foreach ([null, 'invalido', $expired] as $token) {
            $this->app['auth']->forgetGuards();
            $headers = $token ? ['Authorization' => 'Bearer '.$token] : [];
            $this->getJson(route('api.v1.user'), $headers)->assertUnauthorized();
            $this->postJson(route('api.v1.logout'), [], $headers)->assertUnauthorized();
        }
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('actual', ['read'])->plainTextToken;
        $other = $user->createToken('otro', ['read'])->plainTextToken;
        $this->postJson(route('api.v1.logout'), [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();
        $this->getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.$other])->assertOk();
    }

    public function test_login_limita_intentos(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('api.v1.login'), ['email' => 'nadie@example.com', 'password' => 'Incorrecta'])->assertUnauthorized();
        }
        $this->postJson(route('api.v1.login'), ['email' => 'nadie@example.com', 'password' => 'Incorrecta'])->assertTooManyRequests();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sesion_web_no_sustituye_token_api(): void
    {
        $this->actingAs(User::factory()->administrador()->create(), 'web');
        $this->getJson(route('api.v1.user'))->assertUnauthorized();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // TESTE 1 - Bloquear usuário não autenticado
    public function test_usuario_nao_autenticado_recebe_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // TESTE 2 - Permitir acesso ao usuário autenticado
    public function test_usuario_autenticado_pode_acessar_seus_dados(): void
    {
        // Cria um usuário no banco de testes
        $user = User::factory()->create();

        // Simula a autenticação desse usuário pelo Sanctum
        Sanctum::actingAs($user);

        // Faz a requisição à rota protegida
        $response = $this->getJson('/api/user');

        // Verifica se o acesso foi permitido
        $response->assertStatus(200);

        // Confirma que os dados pertencem ao usuário autenticado
        $response->assertJsonPath('id', $user->id);

        $response->assertJsonPath('email', $user->email);
    }
}
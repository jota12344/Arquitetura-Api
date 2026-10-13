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

    // TESTE 3 - Bloquear cadastro sem autenticação
    public function test_usuario_nao_autenticado_nao_pode_criar_produto(): void
    {
    $response = $this->postJson('/api/products', [
        'name' => 'Mouse Gamer',
        'price' => 150,
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseCount('products', 0);
    }

// TESTE 4 - Bloquear atualização sem autenticação
public function test_usuario_nao_autenticado_nao_pode_atualizar_produto(): void
    {
    $product = \App\Models\Product::create([
        'name' => 'Teclado',
        'price' => 100,
    ]);

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => 'Teclado Atualizado',
        'price' => 200,
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Teclado',
        'price' => 100,
    ]);
    }

// TESTE 5 - Bloquear exclusão sem autenticação
public function test_usuario_nao_autenticado_nao_pode_excluir_produto(): void
    {
    $product = \App\Models\Product::create([
        'name' => 'Monitor',
        'price' => 800,
    ]);

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(401);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
    }
}
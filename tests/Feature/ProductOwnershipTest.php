<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_produto_criado_pertence_ao_usuario_autenticado(): void
    {
        // Cria dois usuários para o teste
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        // Simula uma requisição autenticada
        $response = $this->actingAs($usuario)
            ->postJson('/api/products', [
                'name' => 'Mouse Gamer',
                'price' => 150,
                'user_id' => $outroUsuario->id,
            ]);

        // Verifica se o produto foi criado
        $response->assertStatus(201);

        // Verifica se pertence ao usuário autenticado
        $this->assertDatabaseHas('products', [
            'name' => 'Mouse Gamer',
            'user_id' => $usuario->id,
        ]);

        // Verifica se não foi associado ao outro usuário
        $this->assertDatabaseMissing('products', [
            'name' => 'Mouse Gamer',
            'user_id' => $outroUsuario->id,
        ]);
    }
}
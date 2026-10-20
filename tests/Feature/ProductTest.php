<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    protected function autenticarUsuario(): void
{
    $user = User::factory()->create();

    Sanctum::actingAs($user);
}

    // TESTE 1 - Criar um produto
    public function test_deve_criar_um_produto(): void
    {
        $this->autenticarUsuario();

        $dados = [
            'name' => 'Mouse Gamer',
            'price' => 150.00,
        ];

        $response = $this->postJson('/api/products', $dados);

        $response->assertStatus(201);

        $response->assertJsonPath('data.name', 'Mouse Gamer');

        $this->assertDatabaseHas('products', [
            'name' => 'Mouse Gamer',
            'price' => 150.00,
        ]);
    }

    // TESTE 2 - Listar produtos
    public function test_deve_listar_produtos(): void
    {
        Product::create([
            'name' => 'Teclado Mecânico',
            'price' => 250.00,
        ]);

        Product::create([
            'name' => 'Mouse Gamer',
            'price' => 150.00,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data');

        $response->assertJsonFragment([
            'name' => 'Teclado Mecânico',
        ]);

        $response->assertJsonFragment([
            'name' => 'Mouse Gamer',
        ]);
    }

    // TESTE 3 - Consultar produto por ID
    public function test_deve_consultar_um_produto_por_id(): void
    {
        $product = Product::create([
            'name' => 'Monitor Gamer',
            'price' => 899.90,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200);

        $response->assertJsonPath('data.id', $product->id);

        $response->assertJsonPath('data.name', 'Monitor Gamer');
    }

    // TESTE 4 - Produto inexistente retorna 404
    public function test_deve_retornar_404_para_produto_inexistente(): void
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404);

        $response->assertJson([
            'message' => 'Produto não encontrado.',
        ]);
    }

   // TESTE 5 - Atualizar produto
public function test_deve_atualizar_um_produto(): void
{
    $this->autenticarUsuario();

    $product = Product::create([
        'name' => 'Mouse Antigo',
        'price' => 50.00,
        'user_id' => auth()->id(),
    ]);

    $dadosAtualizados = [
        'name' => 'Mouse Gamer RGB',
        'price' => 199.90,
    ];

    $response = $this->putJson(
        "/api/products/{$product->id}",
        $dadosAtualizados
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.name', 'Mouse Gamer RGB');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Mouse Gamer RGB',
        'price' => 199.90,
        'user_id' => auth()->id(),
    ]);
}

   // TESTE 6 - Excluir produto
public function test_deve_excluir_um_produto(): void
{
    $this->autenticarUsuario();

    $product = Product::create([
        'name' => 'Produto para Excluir',
        'price' => 100.00,
        'user_id' => auth()->id(),
    ]);

    $response = $this->deleteJson(
        "/api/products/{$product->id}"
    );

    $response->assertNoContent();

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
}

    // TESTE 7 - Impedir preço negativo no cadastro
    public function test_nao_deve_criar_produto_com_preco_negativo(): void
    {
        $this->autenticarUsuario();

        $dados = [
            'name' => 'Produto Inválido',
            'price' => -50,
        ];

        $response = $this->postJson('/api/products', $dados);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['price']);

        $this->assertDatabaseCount('products', 0);
    }

    // TESTE 8 - Impedir cadastro sem nome
    public function test_nao_deve_criar_produto_sem_nome(): void
    {
        $this->autenticarUsuario();

        $dados = [
            'price' => 100.00,
        ];

        $response = $this->postJson('/api/products', $dados);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('products', 0);
    }

    // TESTE 9 - Impedir atualização com preço negativo
    public function test_nao_deve_atualizar_produto_com_preco_negativo(): void
    {
        $this->autenticarUsuario();

        $product = Product::create([
            'name' => 'Teclado',
            'price' => 150.00,
        ]);

        $response = $this->putJson(
            "/api/products/{$product->id}",
            [
                'name' => 'Teclado Atualizado',
                'price' => -100,
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['price']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Teclado',
            'price' => 150.00,
        ]);
    }

    // TESTE 10 - Impedir atualização de produto inexistente
    public function test_nao_deve_atualizar_produto_inexistente(): void
    {
        $this->autenticarUsuario();

        $response = $this->putJson('/api/products/99999', [
            'name' => 'Produto Atualizado',
            'price' => 100.00,
        ]);

        $response->assertStatus(404);

        $response->assertJson([
            'message' => 'Produto não encontrado.',
        ]);
    }

    // TESTE 11 - Impedir exclusão de produto inexistente
    public function test_nao_deve_excluir_produto_inexistente(): void
    {
        $this->autenticarUsuario();

        $response = $this->deleteJson('/api/products/99999');

        $response->assertStatus(404);

        $response->assertJson([
            'message' => 'Produto não encontrado.',
        ]);
    }
    
    // TESTE 12 - Impedir cadastro com múltiplos campos inválidos
    public function test_nao_deve_criar_produto_com_nome_vazio_e_preco_negativo(): void
    {
        $this->autenticarUsuario();

        $dados = [
        'name' => '',
        'price' => -50,
    ];

    $response = $this->postJson('/api/products', $dados);

    // Verifica se a API rejeitou os dados
    $response->assertStatus(422);

    // Verifica se os dois campos possuem erros
    $response->assertJsonValidationErrors([
        'name',
        'price',
    ]);

    // Garante que nenhum produto foi cadastrado
    $this->assertDatabaseCount('products', 0);
    }

    // TESTE 13 - Impedir atualização de produto de outro usuário
public function test_nao_deve_atualizar_produto_de_outro_usuario(): void
{
    // Cria dois usuários
    $usuario = User::factory()->create();
    $outroUsuario = User::factory()->create();

    // Cria um produto pertencente ao outro usuário
    $product = Product::create([
        'name' => 'Mouse Gamer',
        'price' => 150.00,
        'user_id' => $outroUsuario->id,
    ]);

    // Autentica o primeiro usuário
    $this->actingAs($usuario);

    // Tenta atualizar o produto de outra pessoa
    $response = $this->putJson(
        "/api/products/{$product->id}",
        [
            'name' => 'Mouse Alterado',
            'price' => 200.00,
        ]
    );

    // A API deve negar a atualização
    $response->assertStatus(403);

    // Confirma que os dados originais foram preservados
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Mouse Gamer',
        'user_id' => $outroUsuario->id,
    ]);
}

// TESTE 14 - Impedir exclusão de produto de outro usuário
public function test_nao_deve_excluir_produto_de_outro_usuario(): void
{
    // Cria dois usuários diferentes
    $usuario = User::factory()->create();
    $outroUsuario = User::factory()->create();

    // Cria um produto pertencente ao outro usuário
    $product = Product::create([
        'name' => 'Teclado Mecânico',
        'price' => 250.00,
        'user_id' => $outroUsuario->id,
    ]);

    // Autentica o primeiro usuário
    $this->actingAs($usuario);

    // Tenta excluir o produto de outra pessoa
    $response = $this->deleteJson(
        "/api/products/{$product->id}"
    );

    // Verifica que a API negou a exclusão
    $response->assertStatus(403);

    // Verifica que o produto continua no banco de dados
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Teclado Mecânico',
        'user_id' => $outroUsuario->id,
    ]);
}
}



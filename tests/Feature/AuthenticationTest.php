<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
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

    // TESTE 6 - Bloquear logout sem autenticação
    public function test_usuario_nao_autenticado_nao_pode_fazer_logout(): void
    {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
    }

    // TESTE 7 - Logout deve revogar o token atual
    public function test_logout_deve_revogar_token_atual(): void
    {
    // Cria um usuário no banco de testes
    $user = User::factory()->create();

    // Gera um token real
    $token = $user->createToken('api-token')->plainTextToken;

    // Faz logout com o token
    $response = $this->withToken($token)
        ->postJson('/api/logout');

    // Verifica a resposta do logout
    $response->assertStatus(200);

    $response->assertJson([
        'message' => 'Logout realizado com sucesso.',
    ]);

    // Confirma que o token foi excluído do banco
    $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // TESTE 8 - Login com credenciais válidas
    public function test_usuario_pode_fazer_login_com_credenciais_validas(): void
    {
    // Cria um usuário com senha criptografada por hash
    $user = User::factory()->create([
        'email' => 'teste@exemplo.com',
        'password' => Hash::make('Teste@12345'),
    ]);

    // Envia as credenciais para o endpoint de login
    $response = $this->postJson('/api/login', [
        'email' => 'teste@exemplo.com',
        'password' => 'Teste@12345',
    ]);

    // Verifica se o login foi realizado
    $response->assertStatus(200);

    // Verifica se a resposta contém um token
    $response->assertJsonStructure([
        'message',
        'token',
        'token_type',
    ]);

    $response->assertJsonPath('token_type', 'Bearer');

    // Confirma que o Sanctum armazenou o token
    $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // TESTE 9 - Impedir login com senha incorreta
public function test_usuario_nao_pode_fazer_login_com_senha_incorreta(): void
{
    // Cria um usuário com uma senha válida
    User::factory()->create([
        'email' => 'teste@exemplo.com',
        'password' => Hash::make('Teste@12345'),
    ]);

    // Tenta fazer login com a senha errada
    $response = $this->postJson('/api/login', [
        'email' => 'teste@exemplo.com',
        'password' => 'SenhaErrada123',
    ]);

    // Verifica se o login foi recusado
    $response->assertStatus(422);

    // Verifica se o erro está associado ao campo email
    $response->assertJsonValidationErrors(['email']);

    // Confirma que nenhum token foi criado
    $this->assertDatabaseCount('personal_access_tokens', 0);
}

// TESTE 10 - Impedir login sem campos obrigatórios
public function test_usuario_nao_pode_fazer_login_sem_email_e_senha(): void
{
    // Envia uma requisição sem nenhum dado
    $response = $this->postJson('/api/login', []);

    // O Laravel deve rejeitar os dados inválidos
    $response->assertStatus(422);

    // Verifica os dois campos obrigatórios
    $response->assertJsonValidationErrors([
        'email',
        'password',
    ]);

    // Nenhum token deve ser criado
    $this->assertDatabaseCount('personal_access_tokens', 0);
}
}

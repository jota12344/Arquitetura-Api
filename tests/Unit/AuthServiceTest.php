<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    // TESTE 1 - Login com credenciais válidas
    public function test_deve_gerar_token_com_credenciais_validas(): void
    {
        // Cria um usuário no banco de testes
        User::factory()->create([
            'email' => 'teste@exemplo.com',
            'password' => Hash::make('Teste@12345'),
        ]);

        // Cria uma instância do AuthService
        $service = new AuthService();

        // Executa o login diretamente pelo Service
        $token = $service->login([
            'email' => 'teste@exemplo.com',
            'password' => 'Teste@12345',
        ]);

        // Verifica se recebemos um token em formato de texto
        $this->assertIsString($token);

        // Verifica se o token não está vazio
        $this->assertNotEmpty($token);

        // Confirma que o token foi registrado no banco
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // TESTE 2 - Impedir login com senha incorreta
public function test_nao_deve_gerar_token_com_senha_incorreta(): void
{
    // Cria um usuário com uma senha válida
    User::factory()->create([
        'email' => 'teste@exemplo.com',
        'password' => Hash::make('Teste@12345'),
    ]);

    // Instancia o Service
    $service = new AuthService();

    // Tenta autenticar com a senha errada
    try {
        $service->login([
            'email' => 'teste@exemplo.com',
            'password' => 'SenhaErrada123',
        ]);

        // Se chegar aqui, o Service aceitou uma senha incorreta
        $this->fail('O login deveria ter sido recusado.');
    } catch (ValidationException $e) {
        // Verifica se a exceção contém o erro esperado
        $this->assertArrayHasKey('email', $e->errors());
    }

    // Confirma que nenhum token foi criado
    $this->assertDatabaseCount('personal_access_tokens', 0);
}
}
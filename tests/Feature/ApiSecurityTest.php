<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider protectedEndpoints */
    public function test_sem_accept_retorna_401_em_json(string $method, string $uri): void
    {
        $this->call($method, $uri)->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public static function protectedEndpoints(): array
    {
        return [['GET', '/api/user'], ['POST', '/api/products'], ['PUT', '/api/products/1'],
            ['DELETE', '/api/products/1'], ['POST', '/api/logout']];
    }

    public function test_erros_de_validacao_e_rotas_retornam_json_sem_accept(): void
    {
        $this->get('/api/products?min_price=-1')->assertUnprocessable()
            ->assertJsonValidationErrors('min_price');
        $this->post('/api/login')->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->get('/api/inexistente')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        $this->get('/api/products/abc')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        $this->get('/api/products/999')->assertNotFound()->assertExactJson(['message' => 'Produto não encontrado.']);
    }

    public function test_erro_inesperado_em_producao_nao_expoe_detalhes(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/test-error', function () {
            throw new \RuntimeException('segredo-interno');
        });

        $this->get('/api/test-error')->assertStatus(500)
            ->assertExactJson(['message' => 'Server Error']);
    }

    /** @dataProvider invalidPrices */
    public function test_valida_preco_no_cadastro_e_atualizacao($price): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Original', 'price' => 100, 'user_id' => $user->id]);
        $this->actingAs($user);

        $payload = ['name' => 'Alterado', 'price' => $price];
        $this->post('/api/products', $payload)->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->put("/api/products/{$product->id}", $payload)->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original', 'price' => 100]);
    }

    public static function invalidPrices(): array
    {
        return [[-1], [100000000], ['1.001'], ['1e2'], ['abc'], [[]], [null]];
    }

    public function test_aceita_zero_e_limite_da_coluna_decimal(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['0', '99999999.99'] as $price) {
            $this->post('/api/products', ['name' => 'Produto', 'price' => $price])->assertCreated();
        }
    }

    public function test_usuario_nao_pode_transferir_propriedade_ao_atualizar(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::create(['name' => 'Mouse', 'price' => 100, 'user_id' => $owner->id]);

        $this->actingAs($owner)->put("/api/products/{$product->id}", [
            'name' => 'Mouse novo', 'price' => 200, 'user_id' => $other->id,
        ])->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'user_id' => $owner->id]);
    }

    public function test_policy_bloqueia_outro_usuario_e_produtos_sem_proprietario(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$other->id, null] as $ownerId) {
            $product = Product::create(['name' => 'Original', 'price' => 100, 'user_id' => $ownerId]);
            $this->actingAs($user)->put("/api/products/{$product->id}", ['name' => 'Alterado', 'price' => 200])
                ->assertForbidden()->assertHeader('Content-Type', 'application/json');
            $this->delete("/api/products/{$product->id}")->assertForbidden();
            $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original']);
        }
    }

    public function test_token_invalido_ou_expirado_e_recusado(): void
    {
        $this->withToken('invalid-token')->get('/api/user')->assertUnauthorized();
        $user = User::factory()->create();
        $token = $user->createToken('expired', ['*'], now()->subMinute());
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->get('/api/user')->assertUnauthorized();
    }

    public function test_logout_revoga_apenas_token_atual_e_impede_reuso(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current');
        $other = $user->createToken('other');
        $this->assertSame(hash('sha256', explode('|', $current->plainTextToken, 2)[1]), $current->accessToken->token);

        $this->withToken($current->plainTextToken)->post('/api/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);

        $this->app['auth']->forgetGuards();
        $this->withToken($current->plainTextToken)->get('/api/user')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($other->plainTextToken)->get('/api/user')->assertOk()
            ->assertJsonMissingPath('password')->assertJsonMissingPath('remember_token');
    }

    public function test_login_tem_limite_proprio(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.25']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/api/login', ['email' => 'inexistente@example.com', 'password' => 'invalid'])
                ->assertUnprocessable();
        }
        $this->post('/api/login', ['email' => 'inexistente@example.com', 'password' => 'invalid'])
            ->assertStatus(429)->assertHeader('Retry-After')
            ->assertHeader('Content-Type', 'application/json');
        $this->get('/api/products')->assertOk();
    }
}

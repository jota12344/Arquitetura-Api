<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultas_publicas_sao_paginadas_e_ordenadas(): void
    {
        $categories = Category::factory()->count(12)->create();
        $first = $this->getJson('/api/categories');
        $first->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.id', $categories[0]->id);
        $this->getJson($first->json('links.next'))->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $categories[10]->id);
        $this->get('/api/categories/'.$categories[0]->id)->assertOk()
            ->assertExactJson(['data' => ['id' => $categories[0]->id, 'name' => $categories[0]->name]]);
    }

    public function test_lista_vazia_e_categoria_inexistente(): void
    {
        $this->getJson('/api/categories')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/api/categories/999')->assertNotFound()->assertExactJson(['message' => 'Categoria não encontrada.']);
        $this->get('/api/categories/abc')->assertNotFound();
        $this->get('/api/categories?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_relacionamentos_e_exclusao_preservam_produto(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $this->assertTrue($product->category->is($category));
        $this->assertTrue($category->products->sole()->is($product));
        $category->delete();
        $this->assertNull($product->fresh()->category_id);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_chave_estrangeira_impede_categoria_inexistente(): void
    {
        $this->expectException(QueryException::class);
        Product::factory()->create(['category_id' => 999]);
    }

    public function test_cria_altera_preserva_e_remove_categoria(): void
    {
        $first = Category::factory()->create();
        $second = Category::factory()->create();
        $this->actingAs(User::factory()->create());
        $data = ['name' => 'Mouse', 'price' => 100];
        $response = $this->postJson('/api/products', $data + ['category_id' => $first->id]);
        $response->assertCreated()->assertJsonPath('data.category.id', $first->id);
        $id = $response->json('data.id');
        $this->putJson("/api/products/{$id}", $data + ['category_id' => $second->id])
            ->assertOk()->assertJsonPath('data.category_id', $second->id)->assertJsonPath('data.category.id', $second->id);
        $this->putJson("/api/products/{$id}", $data)->assertOk()->assertJsonPath('data.category.id', $second->id);
        $this->putJson("/api/products/{$id}", $data + ['category_id' => null])
            ->assertOk()->assertJsonPath('data.category_id', null)->assertJsonPath('data.category', null);
        $this->assertDatabaseHas('products', ['id' => $id, 'category_id' => null]);
    }

    public function test_produtos_sem_categoria_continuam_acessiveis(): void
    {
        $product = Product::factory()->create();
        $this->getJson('/api/products/'.$product->id)->assertOk()->assertJsonPath('data.category', null);
        $this->getJson('/api/products')->assertOk()->assertJsonPath('meta.total', 1);
        $this->actingAs(User::factory()->create())->postJson('/api/products', ['name' => 'Mouse', 'price' => 100])
            ->assertCreated()->assertJsonPath('data.category', null);
    }

    /** @dataProvider invalidCategories */
    public function test_valida_categoria_no_cadastro_atualizacao_e_filtro($value): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $data = ['name' => 'Mouse', 'price' => 100, 'category_id' => $value];
        $this->postJson('/api/products', $data)->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->putJson('/api/products/'.$product->id, $data)->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->getJson('/api/products?'.http_build_query(['category_id' => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('products', 1);
        $this->assertNull($product->fresh()->category_id);
    }

    public static function invalidCategories(): array
    {
        return [[999], [-1], ['abc'], [[1]], [1.5]];
    }

    public function test_filtro_categoria_combina_nome_preco_e_paginacao(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(12)->for($category)->create(['name' => 'Mouse', 'price' => 150]);
        Product::factory()->for($category)->create(['name' => 'Mouse', 'price' => 600]);
        Product::factory()->for($category)->create(['name' => 'Teclado', 'price' => 150]);
        Product::factory()->create(['name' => 'Mouse', 'price' => 150]);
        Product::factory()->for(Category::factory())->create(['name' => 'Mouse', 'price' => 150]);
        $this->getJson('/api/products?category_id='.$category->id)->assertOk()->assertJsonPath('meta.total', 14);
        $response = $this->getJson('/api/products?'.http_build_query([
            'category_id' => $category->id, 'name' => 'Mouse', 'min_price' => 100, 'max_price' => 500,
        ]));
        $response->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 12);
        $next = $this->getJson($response->json('links.next'));
        $next->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 12)
            ->assertJsonPath('data.0.category.id', $category->id);
    }

    public function test_lista_carrega_categorias_sem_n_mais_um(): void
    {
        Product::factory()->count(10)->for(Category::factory())->create();
        DB::enableQueryLog();
        try {
            $this->getJson('/api/products')->assertOk()->assertJsonCount(10, 'data');
            $queries = array_filter(DB::getQueryLog(), fn ($query) => preg_match('/from ["`]categories["`]/i', $query['query']));
            $this->assertCount(1, $queries);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_seeder_pode_ser_executado_novamente_sem_duplicar_dados(): void
    {
        $existing = Category::factory()->create();
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseHas('categories', ['id' => $existing->id, 'name' => $existing->name]);
    }

    public function test_associacao_nao_contorna_autorizacao_do_produto(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for(User::factory())->create();
        $this->actingAs(User::factory()->create())->putJson('/api/products/'.$product->id, [
            'name' => 'Mouse', 'price' => 100, 'category_id' => $category->id,
        ])->assertForbidden();
        $this->assertNull($product->fresh()->category_id);
    }
}

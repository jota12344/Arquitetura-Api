<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider validFilters */
    public function test_filtra_produtos_com_limites_inclusivos(array $filters, array $expectedNames): void
    {
        foreach ([['Mouse Grátis', 0], ['Mouse Básico', 100], ['Mouse Gamer', 150.50], ['Mouse Premium', 500], ['Teclado', 150.50]] as [$name, $price]) {
            Product::create(['name' => $name, 'price' => $price]);
        }

        $response = $this->getJson('/api/products?'.http_build_query($filters));

        $response->assertOk()->assertJsonPath('meta.total', count($expectedNames));
        $this->assertSame($expectedNames, array_column($response->json('data'), 'name'));
    }

    public static function validFilters(): array
    {
        return [
            'mínimo isolado' => [['min_price' => 150.50], ['Teclado', 'Mouse Premium', 'Mouse Gamer']],
            'máximo isolado' => [['max_price' => 100], ['Mouse Básico', 'Mouse Grátis']],
            'intervalo' => [['min_price' => 100, 'max_price' => 150.50], ['Teclado', 'Mouse Gamer', 'Mouse Básico']],
            'nome e mínimo' => [['name' => 'Mouse', 'min_price' => 150.50], ['Mouse Premium', 'Mouse Gamer']],
            'nome e máximo' => [['name' => 'Mouse', 'max_price' => 100], ['Mouse Básico', 'Mouse Grátis']],
            'todos os filtros' => [['name' => 'Mouse', 'min_price' => 100, 'max_price' => 150.50], ['Mouse Gamer', 'Mouse Básico']],
            'limites iguais' => [['min_price' => 150.50, 'max_price' => 150.50], ['Teclado', 'Mouse Gamer']],
            'zero' => [['min_price' => 0, 'max_price' => 0], ['Mouse Grátis']],
            'sem resultados' => [['min_price' => 501], []],
            'nome sem resultados' => [['name' => 'Inexistente'], []],
        ];
    }

    /** @dataProvider invalidFilters */
    public function test_rejeita_filtros_invalidos(array $filters, string $field): void
    {
        $this->getJson('/api/products?'.http_build_query($filters))
            ->assertUnprocessable()->assertJsonValidationErrors([$field]);
    }

    public static function invalidFilters(): array
    {
        return [
            'mínimo negativo' => [['min_price' => -1], 'min_price'],
            'máximo negativo' => [['max_price' => -1], 'max_price'],
            'mínimo textual' => [['min_price' => 'abc'], 'min_price'],
            'máximo textual' => [['max_price' => 'abc'], 'max_price'],
            'mínimo array' => [['min_price' => [100]], 'min_price'],
            'máximo array' => [['max_price' => [100]], 'max_price'],
            'mínimo vazio' => [['min_price' => ''], 'min_price'],
            'máximo vazio' => [['max_price' => ''], 'max_price'],
            'intervalo invertido' => [['min_price' => 500, 'max_price' => 100], 'max_price'],
            'nome array' => [['name' => ['Mouse']], 'name'],
            'nome vazio' => [['name' => ''], 'name'],
            'nome longo' => [['name' => str_repeat('a', 256)], 'name'],
            'página zero' => [['page' => 0], 'page'],
            'página negativa' => [['page' => -1], 'page'],
            'página fracionária' => [['page' => 1.5], 'page'],
            'página textual' => [['page' => 'abc'], 'page'],
            'página array' => [['page' => [1]], 'page'],
        ];
    }

    public function test_busca_nome_contendo_zero(): void
    {
        Product::create(['name' => 'Mouse 0', 'price' => 100]);
        Product::create(['name' => 'Teclado', 'price' => 100]);

        $this->getJson('/api/products?name=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Mouse 0');
    }

    public function test_paginacao_preserva_filtros_e_ordenacao(): void
    {
        $ids = [];
        for ($i = 1; $i <= 12; $i++) {
            $ids[] = Product::create(['name' => "Mouse {$i}", 'price' => 150])->id;
        }
        Product::create(['name' => 'Mouse Caro', 'price' => 501]);
        Product::create(['name' => 'Mouse Barato', 'price' => 99]);
        Product::create(['name' => 'Teclado', 'price' => 150]);

        $filters = ['name' => 'Mouse', 'min_price' => '100', 'max_price' => '500'];
        $first = $this->getJson('/api/products?'.http_build_query($filters + ['ignored' => 'value']));
        $first->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 12);
        $this->assertSame(array_slice(array_reverse($ids), 0, 10), array_column($first->json('data'), 'id'));

        foreach (['first', 'last', 'next'] as $link) {
            parse_str(parse_url($first->json("links.{$link}"), PHP_URL_QUERY), $query);
            foreach ($filters as $key => $value) {
                $this->assertSame($value, $query[$key]);
            }
            $this->assertArrayNotHasKey('ignored', $query);
        }

        $second = $this->getJson($first->json('links.next'));
        $second->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.total', 12)
            ->assertJsonPath('links.next', null);
        $this->assertSame([$ids[1], $ids[0]], array_column($second->json('data'), 'id'));

        $previous = $this->getJson($second->json('links.prev'));
        $previous->assertOk();
        $this->assertSame($first->json('data'), $previous->json('data'));
    }

    public function test_pagina_apos_a_ultima_retorna_lista_vazia(): void
    {
        Product::create(['name' => 'Mouse', 'price' => 100]);

        $this->getJson('/api/products?name=Mouse&page=2')
            ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 1);
    }
}

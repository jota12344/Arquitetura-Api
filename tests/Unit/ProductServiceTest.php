<?php

namespace Tests\Unit;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\ProductService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    // TESTE 1 - Listar todos os produtos
    public function test_deve_listar_todos_os_produtos(): void
    {
        $products = collect([
            new Product(['name' => 'Mouse Gamer', 'price' => 150]),
            new Product(['name' => 'Teclado', 'price' => 250]),
        ]);

        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('getAll')
            ->once()
            ->andReturn($products);

        $service = new ProductService($repository);

        $resultado = $service->getAll();

        $this->assertSame($products, $resultado);
    }

    // TESTE 2 - Consultar produto por ID
    public function test_deve_buscar_produto_por_id(): void
    {
        $product = new Product([
            'name' => 'Monitor Gamer',
            'price' => 899.90,
        ]);

        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($product);

        $service = new ProductService($repository);

        $resultado = $service->findById(1);

        $this->assertSame($product, $resultado);
    }

    // TESTE 3 - Criar produto
    public function test_deve_criar_um_produto(): void
    {
        $dados = [
            'name' => 'Mouse Gamer',
            'price' => 150.00,
        ];

        $product = new Product($dados);

        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('create')
            ->once()
            ->with($dados)
            ->andReturn($product);

        $service = new ProductService($repository);

        $resultado = $service->create($dados);

        $this->assertSame($product, $resultado);
    }

    // TESTE 4 - Atualizar produto
    public function test_deve_atualizar_um_produto(): void
    {
        $dados = [
            'name' => 'Mouse Atualizado',
            'price' => 199.90,
        ];

        $product = new Product($dados);

        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('update')
            ->once()
            ->with(1, $dados)
            ->andReturn($product);

        $service = new ProductService($repository);

        $resultado = $service->update(1, $dados);

        $this->assertSame($product, $resultado);
    }

    // TESTE 5 - Excluir produto
    public function test_deve_excluir_um_produto(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('delete')
            ->once()
            ->with(1);

        $service = new ProductService($repository);

        $service->delete(1);

        // Mockery verifica se delete(1) foi chamado uma vez
        $this->assertTrue(true);
    }
}

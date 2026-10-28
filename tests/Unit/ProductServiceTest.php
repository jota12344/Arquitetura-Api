<?php

namespace Tests\Unit;

use App\Interfaces\ProductRepositoryInterface;
use App\Services\ProductService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    /** @var ProductRepositoryInterface&MockInterface */
    protected $repositoryMock;

    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(
            ProductRepositoryInterface::class
        );

        $this->service = new ProductService(
            $this->repositoryMock
        );
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    // TESTE 1 - Listar todos os produtos
    public function test_deve_listar_todos_os_produtos(): void
    {
        $produtos = [
            ['id' => 1, 'name' => 'Mouse', 'price' => 100],
            ['id' => 2, 'name' => 'Teclado', 'price' => 200],
        ];

        $this->repositoryMock
        ->shouldReceive('getAll')
        ->once()
        ->with([])
        ->andReturn($produtos);
        $resultado = $this->service->getAll();

        $this->assertEquals($produtos, $resultado);
    }

    public function test_deve_encaminhar_filtros_ao_repository(): void
    {
        $filters = ['name' => 'Mouse', 'min_price' => 100, 'max_price' => 500];
        $products = [['id' => 1, 'name' => 'Mouse Gamer', 'price' => 150]];

        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($filters)
            ->andReturn($products);

        $this->assertSame($products, $this->service->getAll($filters));
    }

    // TESTE 2 - Buscar produto pelo ID
    public function test_deve_buscar_produto_pelo_id(): void
    {
        $produto = [
            'id' => 1,
            'name' => 'Mouse',
            'price' => 100,
        ];

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($produto);

        $resultado = $this->service->findById(1);

        $this->assertEquals($produto, $resultado);
    }

    // TESTE 3 - Criar produto
    public function test_deve_criar_produto(): void
    {
        $dados = [
            'name' => 'Monitor',
            'price' => 800,
        ];

        $produtoCriado = [
            'id' => 3,
            'name' => 'Monitor',
            'price' => 800,
        ];

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with($dados)
            ->andReturn($produtoCriado);

        $resultado = $this->service->create($dados);

        $this->assertEquals($produtoCriado, $resultado);
    }

    // TESTE 4 - Atualizar produto
    public function test_deve_atualizar_produto(): void
    {
        $dados = [
            'name' => 'Mouse Atualizado',
            'price' => 150,
        ];

        $produtoAtualizado = [
            'id' => 1,
            'name' => 'Mouse Atualizado',
            'price' => 150,
        ];

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->with(1, $dados)
            ->andReturn($produtoAtualizado);

        $resultado = $this->service->update(1, $dados);

        $this->assertEquals($produtoAtualizado, $resultado);
    }

    // TESTE 5 - Excluir produto
    public function test_deve_excluir_produto(): void
    {
        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        $resultado = $this->service->delete(1);

        $this->assertTrue($resultado);
    }
}

<?php

namespace Tests\Unit;

use App\Interfaces\CategoryRepositoryInterface;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use PHPUnit\Framework\TestCase;

class CategoryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_delega_listagem_paginada_ao_repository(): void
    {
        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $page = Mockery::mock(LengthAwarePaginator::class);
        $repository->shouldReceive('getAll')->once()->andReturn($page);
        $this->assertSame($page, (new CategoryService($repository))->getAll());
    }

    public function test_delega_consulta_por_id_ao_repository(): void
    {
        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $category = new Category(['name' => 'Informática']);
        $repository->shouldReceive('findById')->with(1)->once()->andReturn($category);
        $this->assertSame($category, (new CategoryService($repository))->findById(1));
    }

    public function test_propaga_categoria_inexistente(): void
    {
        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('findById')->with(999)->once()->andThrow(ModelNotFoundException::class);
        $this->expectException(ModelNotFoundException::class);
        (new CategoryService($repository))->findById(999);
    }
}

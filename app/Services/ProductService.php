<?php

namespace App\Services;

use App\Interfaces\ProductRepositoryInterface;

class ProductService
{
    protected ProductRepositoryInterface $repository;

    // Injeta o Repository através da interface
    public function __construct(
        ProductRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    public function getAll(array $filters = [])
    {
    return $this->repository->getAll($filters);
    }

    // Busca um produto pelo ID
    public function findById(int $id)
    {
        return $this->repository->findById($id);
    }

    // Cria um novo produto
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    // Atualiza um produto existente
    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    // Exclui um produto
    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
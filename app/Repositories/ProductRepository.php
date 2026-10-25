<?php

namespace App\Repositories;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;


class ProductRepository implements ProductRepositoryInterface
{
 
public function getAll(array $filters = [])
{
    // Inicia a consulta
    $query = Product::query();

    // Filtra pelo nome
    if (!empty($filters['name'])) {
        $query->where(
            'name',
            'like',
            '%' . $filters['name'] . '%'
        );
    }

    // Filtra pelo preço mínimo
    if (isset($filters['min_price'])) {
        $query->where('price', '>=', $filters['min_price']);
    }

    // Filtra pelo preço máximo
    if (isset($filters['max_price'])) {
        $query->where('price', '<=', $filters['max_price']);
    }

    // Ordena e pagina os resultados
    return $query->orderBy('id', 'desc')->paginate(10);
}

    public function findById(int $id)
    {
        return Product::findOrFail($id);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(int $id, array $data)
    {
        $product = Product::findOrFail($id);

        $product->update($data);

        return $product;
    }

    public function delete(int $id)
    {
        $product = Product::findOrFail($id);

        return $product->delete();
    }
}
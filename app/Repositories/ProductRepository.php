<?php

namespace App\Repositories;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;


class ProductRepository implements ProductRepositoryInterface
{
    public function getAll()
    {
        return Product::all();
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
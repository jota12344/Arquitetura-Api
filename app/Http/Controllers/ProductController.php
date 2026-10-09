<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $products = $this->service->getAll();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->service->create(
            $request->validated()
        );

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id)
    {
        $product = $this->service->findById($id);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->service->update(
            $id,
            $request->validated()
        );

        return new ProductResource($product);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}

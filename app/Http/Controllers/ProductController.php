<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;

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

    $data = $request->validated();
    $data['user_id'] = $request->user()->id;

    $product = $this->service->create($data);

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
    $product = $this->service->findById($id);
    Gate::authorize('update', $product);


    $updatedProduct = $this->service->update(
        $id,
        $request->validated()
    );

    return new ProductResource($updatedProduct);
    }

    public function destroy(int $id)
{
    $product = $this->service->findById($id);

    Gate::authorize('delete', $product);

    $this->service->delete($id);

    return response()->noContent();
}
}

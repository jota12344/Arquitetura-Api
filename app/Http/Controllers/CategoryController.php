<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        return CategoryResource::collection($this->service->getAll());
    }

    public function show(int $id): CategoryResource
    {
        return new CategoryResource($this->service->findById($id));
    }
}

<?php

namespace App\Repositories;

use App\Interfaces\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAll(): LengthAwarePaginator
    {
        return Category::query()->orderBy('id')->paginate(10);
    }

    public function findById(int $id): Category
    {
        return Category::findOrFail($id);
    }
}

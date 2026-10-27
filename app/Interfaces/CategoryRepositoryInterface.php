<?php

namespace App\Interfaces;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface
{
    public function getAll(): LengthAwarePaginator;

    public function findById(int $id): Category;
}

<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    // Usuários autenticados podem criar produtos
    public function create(User $user): bool
    {
        return true;
    }

    // Apenas o proprietário pode atualizar
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    // Apenas o proprietário pode excluir
    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }
}
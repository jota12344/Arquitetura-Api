<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'user_id',
    ];

    // Cada produto pertence a um usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
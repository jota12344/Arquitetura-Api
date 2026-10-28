<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function login(array $dados): string
    {
        // Procura o usuário pelo e-mail
        $user = User::where('email', $dados['email'])->first();

        // Verifica se o usuário existe e se a senha está correta
        if (!$user || !Hash::check($dados['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas são inválidas.'],
            ]);
        }

        // Cria e retorna o token de autenticação
        return $user->createToken('api-token')->plainTextToken;
    }

   public function logout(User $user): void
{
    // Recupera o token utilizado na requisição
    $token = $user->currentAccessToken();

    // Verifica se é um token persistido pelo Sanctum
    if ($token instanceof PersonalAccessToken) {
        // Revoga o token atual
        $token->delete();
    }
}
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Valida os dados recebidos
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Procura o usuário pelo e-mail
        $user = User::where('email', $dados['email'])->first();

        // Verifica se o usuário existe e se a senha está correta
        if (!$user || !Hash::check($dados['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas são inválidas.'],
            ]);
        }

        // Gera um token de acesso
        $token = $user->createToken('api-token')->plainTextToken;

        // Retorna o token para o cliente
        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
    // Recupera o token utilizado na requisição
    $token = $request->user()->currentAccessToken();

    // Revoga o token atual
    $token->delete();

    return response()->json([
        'message' => 'Logout realizado com sucesso.',
    ], 200);
    }
}
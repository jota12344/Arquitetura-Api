<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $service;

    // O Laravel injeta automaticamente o AuthService
    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function login(LoginRequest $request)
    {
        // Recupera os dados validados pelo LoginRequest
        $dados = $request->validated();

        // Delega a autenticação ao Service
        $token = $this->service->login($dados);

        // Retorna a resposta HTTP
        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        // Delega a revogação do token ao Service
        $this->service->logout($request->user());

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ], 200);
    }
}
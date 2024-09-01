<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {

        //valida email e senha
        if (
            Auth::attempt([
                "email" => $request->email,
                "password" => $request->password
            ])
        ) {

            //Pegar dados do usuario

            $user = Auth::user();

            $token = $request->user()->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login realizado com sucesso',
                'user' => $user,
                'token' => $token
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Login ou senha inválidos'
            ], 401);
        }
    }
    public function logout(User $user): JsonResponse
    {
        try {
            $user->tokens()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Logout efetuado com sucesso'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'=> false,
                'message' => 'Erro ao efetuar o logout',
            ], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function register(UserRequest $request): JsonResponse
    {
        /*
         * Cria um usuário com os campos definidos na model $fillable
         * E retorna os dados do novo usuário, tratamentos de erro em UserRequest, requests/UserRequests
         * @param \App\Models\User
         * @return \Illuminate\Http\JsonResponse
         */
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'avatar' => $request->avatar,
                'capa' => $request->capa,
                'bio' => $request->bio,
                'languages' => json_encode($request->languages),
                'collections' => json_encode($request->collections), // Converte o array em JSON
                'genres' => json_encode($request->genres), // Converte o array em JSON
            ]);

            //confirma cadastro
            DB::commit();

            if (Auth::attempt(["email" => $request->email, "password" => $request->password])) {
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'status' => true,
                    'message' => 'Usuário criado com sucesso',
                    'user' => $user,
                    'token' => $token
                ], 201);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Falha ao criar o usuário',
                ], 400);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao criar o usuário',
                'e' => $e
            ], 400);
        }


    }
    public function index(): JsonResponse
    {
        $user = Auth::user();
        return response()->json([
            'status' => true,
            'user' => $user,
        ], 200);
    }

    public function update(UserRequest $request): JsonResponse
    {
        $user = Auth::user(); 
        DB::beginTransaction();

        try {
            $user->update($request->only($user->getFillable()));

            DB::commit();
            return response()->json([
                'status' => true,
                'user' => $user,
                'message' => 'Usuário editado com sucesso!',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao editar o usuário.',
            ], 400);
        }

    }

    public function destroy(): JsonResponse
    {
        $user = Auth::user();
        try {
            $user->delete();
            return response()->json([
                'status' => true,
                'message' => 'Usuário excluido com sucesso',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => true,
                'message' => 'Usuário não excluido',
            ], 400);
        }




    }

}

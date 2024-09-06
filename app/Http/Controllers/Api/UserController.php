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

    public function index(): JsonResponse
    {
        /*
         * Retorna uma lista paginada de usuários
         * @return \Illuminate\Http\JsonResponse
         */
        $users = User::orderBy('id', 'DESC')->paginate(15);
        return response()->json([
            'status' => true,
            'message' => $users,
        ], 200);
    }

    public function show(User $user): JsonResponse
    {
        /*
         * Retorna um usuário pelo ID
         * @param \App\Models\User
         * @return \Illuminate\Http\JsonResponse
         */
        return response()->json([
            'status' => true,
            'user' => $user,
        ], 200);
    }

    public function store(UserRequest $request): JsonResponse
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
    public function update(UserRequest $request): JsonResponse
    {
        /*
         * Atualiza um usuário com os campos definidos na model $fillable
         * E retorna os dados do novo usuário, tratamentos de erro em UserRequest, requests/UserRequests
         */
        $user = Auth::user(); // Pega o usuário autenticado pelo token
        DB::beginTransaction();

        try {
            // Atualiza apenas os campos que estão no $fillable da Model User
            $user->update($request->only($user->getFillable()));

            DB::commit();
            return response()->json([
                'status' => true,
                'user' => $user,
                'message' => 'Usuário editado com sucesso',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao editar o usuário',
            ], 400);
        }

    }

    public function destroy(User $user): JsonResponse
    {
        /*
         * Exclui um usuário conforme o id enviado por User $user 
         * @param \App\Models\User
         * @return \Illuminate\Http\JsonResponse
         */
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

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
                'languages' => $request->languages,
                'collections' => json_encode($request->collections), // Converte o array em JSON
            ]);

            //confirma cadastro
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Usuário criado com sucesso',
                'user' => $user,
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao criar o usuário',
                'e' => $e
            ], 400);
        }


    }


    public function update(UserRequest $request, User $user): JsonResponse
    {
        /*
         * Atualiza um usuário com os campos definidos na model $fillable
         * E retorna os dados do novo usuário, tratamentos de erro em UserRequest, requests/UserRequests
         * @param \App\Models\User
         * @return \Illuminate\Http\JsonResponse
         */
        DB::beginTransaction();
        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao editar o usuário',
            ], 400);
        }

        return response()->json([
            'status' => true,
            'user' => $user,
            'message' => 'Usuário editado com sucesso',
        ], 200);

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

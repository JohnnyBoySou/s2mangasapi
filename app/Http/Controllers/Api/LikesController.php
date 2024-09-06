<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikesController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user(); // Pega o usuário autenticado pelo token

        try {
            // Verifica se o usuário tem o campo 'likes'
            if ($user->likes) {
                // Decodifica o JSON armazenado no campo likes
                $likes = json_decode($user->likes, true);

                // Verifica se likes é um array e não está vazio
                if (is_array($likes) && !empty($likes)) {
                    return response()->json([
                        'status' => true,
                        'likes' => $likes, // Retorna a lista de mangás curtidos
                        'message' => 'Lista de mangás curtidos obtida com sucesso',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => true,
                        'likes' => [],
                        'message' => 'Nenhum mangá curtido encontrado',
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => true,
                    'likes' => [],
                    'message' => 'Nenhum mangá curtido encontrado',
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao listar os mangás curtidos',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(UserRequest $request): JsonResponse
    {
        $user = Auth::user(); // Pega o usuário autenticado pelo token
        DB::beginTransaction();

        try {
            $message = '';
            $data = '';

            if ($request->has('manga_id') && $request->has('name') && $request->has('capa')) {
                $mangaId = $request->input('manga_id');
                $name = $request->input('name');
                $capa = $request->input('capa');

                // Recupera o JSON de likes e converte em um array PHP
                $likesJson = $user->likes;
                $likes = json_decode($likesJson, true);

                if (!is_array($likes)) {
                    $likes = [];
                }

                // Verifica se o mangá já está na lista de likes pelo ID
                $mangaIndex = array_search($mangaId, array_column($likes, 'id'));

                if ($mangaIndex !== false) {
                    // Se o mangá for encontrado, remove-o
                    unset($likes[$mangaIndex]);
                    $likes = array_values($likes); // Reindexa o array
                    $message = 'Mangá removido dos likes';
                    $data = false;
                } else {
                    // Se o mangá não for encontrado, adiciona-o com os campos fornecidos
                    $likes[] = [
                        'id' => $mangaId,
                        'name' => $name,
                        'capa' => $capa,
                    ];
                    $message = 'Mangá adicionado aos likes';
                    $data = true;
                }

                // Atualiza o campo likes no usuário
                $user->likes = json_encode($likes); // Converte o array atualizado para JSON
                $user->save(); // Salva as mudanças
            } else {
                // Caso não tenha os parâmetros obrigatórios
                return response()->json([
                    'status' => false,
                    'message' => 'Parâmetros incompletos (manga_id, name ou capa ausentes)',
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar os likes do usuário',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

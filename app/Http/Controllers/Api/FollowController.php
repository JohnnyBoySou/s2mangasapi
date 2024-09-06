<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
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

                // Recupera o JSON de follows e converte em um array PHP
                $followsJson = $user->follows;
                $follows = json_decode($followsJson, true);

                if (!is_array($follows)) {
                    $follows = [];
                }

                // Verifica se o mangá já está na lista de follows pelo ID
                $mangaIndex = array_search($mangaId, array_column($follows, 'id'));

                if ($mangaIndex !== false) {
                    // Se o mangá for encontrado, remove-o
                    unset($follows[$mangaIndex]);
                    $follows = array_values($follows); // Reindexa o array
                    $message = 'Mangá removido dos follows';
                    
                    $data = false;
                } else {
                    // Se o mangá não for encontrado, adiciona-o com os campos fornecidos
                    $follows[] = [
                        'id' => $mangaId,
                        'name' => $name,
                        'capa' => $capa,
                    ];
                    $message = 'Mangá adicionado aos follows';
                    $data = true;
                }

                // Atualiza o campo follows no usuário
                $user->follows = json_encode($follows); // Converte o array atualizado para JSON
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
                'data' => $data,
                'message' => $message,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar os follows do usuário',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    public function index(): JsonResponse
    {
        $user = Auth::user(); // Pega o usuário autenticado pelo token

        try {
            // Verifica se o usuário tem o campo 'follows'
            if ($user->follows) {
                // Decodifica o JSON armazenado no campo follows
                $follows = json_decode($user->follows, true);

                // Verifica se follows é um array e não está vazio
                if (is_array($follows) && !empty($follows)) {
                    return response()->json([
                        'status' => true,
                        'follows' => $follows, // Retorna a lista de mangás seguidos
                        'message' => 'Lista de mangás seguidos obtida com sucesso',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => true,
                        'follows' => [],
                        'message' => 'Nenhum mangá seguido encontrado',
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => true,
                    'follows' => [],
                    'message' => 'Nenhum mangá seguido encontrado',
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao listar os mangás seguidos',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

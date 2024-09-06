<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user(); // Pega o usuário autenticado pelo token

        try {
            // Verifica se o usuário tem o campo 'progress'
            if ($user->progress) {
                // Decodifica o JSON armazenado no campo progress
                $progress = json_decode($user->progress, true);

                // Verifica se progress é um array e não está vazio
                if (is_array($progress) && !empty($progress)) {
                    return response()->json([
                        'status' => true,
                        'progress' => $progress, // Retorna a lista de mangás em progresso
                        'message' => 'Lista de mangás em progresso obtida com sucesso',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => true,
                        'progress' => [],
                        'message' => 'Nenhum mangá em progresso encontrado',
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => true,
                    'progress' => [],
                    'message' => 'Nenhum mangá em progresso encontrado',
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao listar os mangás em progresso',
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

                // Recupera o JSON de progress e converte em um array PHP
                $progressJson = $user->progress;
                $progress = json_decode($progressJson, true);

                if (!is_array($progress)) {
                    $progress = [];
                }

                // Verifica se o mangá já está na lista de progress pelo ID
                $mangaIndex = array_search($mangaId, array_column($progress, 'id'));

                if ($mangaIndex !== false) {
                    // Se o mangá for encontrado, remove-o
                    unset($progress[$mangaIndex]);
                    $progress = array_values($progress); // Reindexa o array
                    $message = 'Mangá removido do progresso';
                    $data = false;
                } else {
                    // Se o mangá não for encontrado, adiciona-o com os campos fornecidos
                    $progress[] = [
                        'id' => $mangaId,
                        'name' => $name,
                        'capa' => $capa,
                    ];
                    $message = 'Mangá adicionado ao progresso';
                    $data = true;
                }

                // Atualiza o campo progress no usuário
                $user->progress = json_encode($progress); // Converte o array atualizado para JSON
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
                'message' => 'Falha ao atualizar o progresso do usuário',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

}

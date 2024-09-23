<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;

class LibraryController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        try {
            if ($user->follows) {
                $follows = json_decode($user->follows, true);
                $likes = json_decode($user->likes, true);
                $completes = json_decode($user->completes, true);
                $progress = json_decode($user->progress, true);

                return response()->json([
                    'status' => true,
                    'follows' => $follows,
                    'likes' => $likes,
                    'completes' => $completes,
                    'progress' => $progress,
                    'message' => 'Lista de mangás obtida com sucesso',
                ], 200);

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

    public function single($id): JsonResponse
    {
        $user = Auth::user();
        try {
            // Recebe os parâmetros de paginação
            $page = request()->input('page', 1); // Página atual, padrão é 1
            $limit = request()->input('limit', 10); // Limite por página, padrão é 10
            $offset = ($page - 1) * $limit; // Cálculo para o offset

            if ($user->follows) {
                $progress = json_decode($user->progress, true);
                $follows = json_decode($user->follows, true);
                $likes = json_decode($user->likes, true);
                $completes = json_decode($user->completes, true);

                // Escolhe o conjunto de dados baseado no $id
                $data = $id === 'follows' ? $follows : ($id === 'likes' ? $likes : ($id === 'completes' ? $completes : ($id === 'progress' ? $progress : [])));

                // Total de itens no dataset
                $total = count($data);

                // Aplica a paginação no array
                $paginatedData = array_slice($data, $offset, $limit);

                return response()->json([
                    'status' => true,
                    'data' => $paginatedData,
                    'total' => $total, // Total de itens
                    'page' => $page, // Página atual
                    'limit' => $limit, // Itens por página
                    'message' => 'Lista de mangás obtida com sucesso',
                ], 200);

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

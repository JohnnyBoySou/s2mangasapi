<?php


namespace App\Http\Controllers\Api;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompletesController extends Controller
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

                // Recupera o JSON de completes e converte em um array PHP
                $completesJson = $user->completes;
                $completes = json_decode($completesJson, true);

                if (!is_array($completes)) {
                    $completes = [];
                }

                // Verifica se o mangá já está na lista de completes pelo ID
                $mangaIndex = array_search($mangaId, array_column($completes, 'id'));

                if ($mangaIndex !== false) {
                    // Se o mangá for encontrado, remove-o
                    unset($completes[$mangaIndex]);
                    $completes = array_values($completes); // Reindexa o array
                    $message = 'Mangá removido dos completes';
                    
                    $data = false;
                } else {
                    // Se o mangá não for encontrado, adiciona-o com os campos fornecidos
                    $completes[] = [
                        'id' => $mangaId,
                        'name' => $name,
                        'capa' => $capa,
                    ];
                    $message = 'Mangá adicionado aos completes';
                    
                    $data = true;
                }

                // Atualiza o campo completes no usuário
                $user->completes = json_encode($completes); // Converte o array atualizado para JSON
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
                'message' => 'Falha ao atualizar os completes do usuário',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    public function index(): JsonResponse
    {
        $user = Auth::user(); 

        try {
            if ($user->completes) {
                $completes = json_decode($user->completes, true);
                if (is_array($completes) && !empty($completes)) {
                    return response()->json([
                        'status' => true,
                        'completes' => $completes, 
                        'message' => 'Lista de mangás completados obtida com sucesso',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => true,
                        'completes' => [],
                        'message' => 'Nenhum mangá completado encontrado',
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => true,
                    'completes' => [],
                    'message' => 'Nenhum mangá completado encontrado',
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao listar os mangás completados',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\MangalistRequest;
use App\Http\Controllers\Controller;
use App\Models\Mangalist;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MangalistController extends Controller
{
    public function index(): JsonResponse
    {
        $mangalist = Mangalist::withCount('likes')->orderByDesc('likes_count')->paginate(10); // Remove o filtro por usuário
    
        return response()->json([
            'status' => true,
            'mangalist' => $mangalist,
        ], 200);
    }

    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $mangalist = $user->mangalist()->find($id);
        if (!$mangalist) {
            return response()->json([
                'status' => false,
                'message' => 'Mangálist não encontrada',
            ], 404);
        }
        return response()->json([
            'status' => true,
            'mangalist' => $mangalist,
        ], 200);
    }

    public function store(MangalistRequest $request): JsonResponse
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            $mangalist = new Mangalist([
                'name' => $request->input('name'),
                'capa' => $request->input('capa'),
                'color' => $request->input('color'),
                'descricao' => $request->input('descricao'),
                'mangas_id' => json_encode($request->input('mangas_id')),
                'type' => $request->input('type'),
                'user_id' => $user->id,
            ]);

            $mangalist->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'mangalist' => $mangalist,
                'message' => 'Mangálist criada com sucesso!',
            ], 201); // Código 201 para recurso criado com sucesso
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao criar a mangalist!',
                'error' => $e->getMessage(), // Opcional: para ajudar na depuração
            ], 400);
        }
    }
    public function update(MangalistRequest $request, $id): JsonResponse
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            $mangalist = Mangalist::where('id', $id)
                ->where('user_id', $user->id) // Garante que o usuário autenticado é o proprietário da coleção
                ->firstOrFail();

            $mangalist->update($request->only($mangalist->getFillable()));

            DB::commit();
            return response()->json([
                'status' => true,
                'mangalist' => $mangalist,
                'message' => 'Mangálist atualizada com sucesso',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar a Mangálist',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $mangalist = Mangalist::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $mangalist->delete();

            return response()->json([
                'status' => true,
                'message' => 'Mangálist excluída com sucesso!',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Mangálist não encontrada!',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao excluir a coleção!',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function searchAll($search): JsonResponse
    {
        $mangalist = Mangalist::where('name', 'like', '%' . $search . '%')
            ->paginate(10);

        // Verifica se há resultados, se não, retorna um array vazio
        $response = $mangalist->isEmpty()
            ? []
            : $mangalist;

        return response()->json([
            'status' => true,
            'mangalists' => $response,
        ], 200);
    }

    public function toggleLike($id): JsonResponse
    {
        $user = Auth::user(); 

        DB::beginTransaction();

        try {
            $mangalist = Mangalist::find($id);

            if (!$mangalist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mangalist não encontrada.',
                ], 404);
            }

            // Procura se já existe um like do usuário para essa coleção
            $existingLike = DB::table('mangalists_likes')
                ->where('mangalist_id', $id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {
                // Remover o like existente
                DB::table('mangalists_likes')
                    ->where('id', $existingLike->id)
                    ->delete();

                DB::commit();

                return response()->json([
                    'status' => true,
                    'liked' => false,
                    'message' => 'Curtida removida.',
                ], 200);
            } else {
                // Adicionar uma nova curtida
                DB::table('mangalists_likes')->insert([
                    'mangalist_id' => $id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'liked' => true,
                    'message' => 'Curtida adicionada.',
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Erro ao alternar curtida.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function userMangalist(): JsonResponse
    {
        $user = Auth::user();
    
        $mangalist = Mangalist::withCount('likes') // Inclui a contagem de curtidas
            ->whereHas('likes', function ($query) use ($user) {
                $query->where('user_id', $user->id); // Filtra pelos likes do usuário atual
            })
            ->orderByDesc('likes_count') // Ordena pela quantidade de curtidas
            ->paginate(10);
    
        return response()->json([
            'status' => true,
            'mangalist' => $mangalist,
        ], 200);
    }

}

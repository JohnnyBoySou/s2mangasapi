<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\CollectionRequest;
use App\Http\Requests\MangaRequest;
use App\Models\Collection;
use App\Models\Manga;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CollectionController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $collections = Collection::where('user_id', $user->id)->paginate(10);
        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $collection = Collection::where('user_id', $user->id)->where('id', $id)->first();

        if (!$collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found',
            ], 404);
        }
        return response()->json([
            'status' => true,
            'collection' => $collection,
        ], 200);
    }

    public function store(CollectionRequest $request): JsonResponse
    {
        // Recupera o usuário autenticado
        $user = Auth::user();

        DB::beginTransaction();

        try {
            // Cria uma nova coleção e associa ao usuário autenticado
            $collection = new Collection([
                'name' => $request->input('name'),
                'capa' => $request->input('capa'),
                'user_id' => $user->id, // Preenche o user_id com o ID do usuário autenticado
            ]);

            // Salva a coleção no banco de dados
            $collection->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'collection' => $collection,
                'message' => 'Coleção criada com sucesso',
            ], 201); // Código 201 para recurso criado com sucesso
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao criar a coleção',
                'error' => $e->getMessage(), // Opcional: para ajudar na depuração
            ], 400);
        }
    }

    public function update(CollectionRequest $request, $id): JsonResponse
    {
        // Recupera o usuário autenticado
        $user = Auth::user();

        DB::beginTransaction();

        try {
            // Recupera a coleção com base no ID fornecido
            $collection = Collection::where('id', $id)
                ->where('user_id', $user->id) // Garante que o usuário autenticado é o proprietário da coleção
                ->firstOrFail();

            // Atualiza a coleção com os dados fornecidos na requisição
            $collection->update($request->only($collection->getFillable())); // Adapte conforme necessário

            DB::commit();
            return response()->json([
                'status' => true,
                'collection' => $collection,
                'message' => 'Coleção atualizada com sucesso',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar a coleção',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    public function destroy($id): JsonResponse
    {
        try {
            // Recupera o usuário autenticado
            $user = Auth::user();

            // Verifica se a coleção existe e pertence ao usuário autenticado
            $collection = Collection::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail(); // Lança ModelNotFoundException se não encontrar

            // Exclui a coleção
            $collection->delete();

            return response()->json([
                'status' => true,
                'message' => 'Coleção excluída com sucesso!',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Retorna 404 se a coleção não for encontrada
            return response()->json([
                'status' => false,
                'message' => 'Coleção não encontrada!',
            ], 404);
        } catch (Exception $e) {
            // Retorna 400 para outros erros
            return response()->json([
                'status' => false,
                'message' => 'Falha ao excluir a coleção!',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function toggle(MangaRequest $request, $id): JsonResponse
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            // Recupera a coleção com base no ID fornecido
            // Verifica se a coleção existe e pertence ao usuário autenticado
            $collection = Collection::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$collection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Coleção não encontrada ou não autorizada',
                ], 404);
            }

            // Obtém o array de mangas_id da coleção
            $mangas = json_decode($collection->mangas_id, true) ?? []; // Converte o JSON para um array

            $mangaId = $request->input('id');
            $mangaName = $request->input('name');
            $mangaCapa = $request->input('capa');

            // Verifica se o mangá já está na coleção
            $mangaIndex = array_search($mangaId, array_column($mangas, 'id'));

            if ($mangaIndex !== false) {
                // Mangá encontrado, remove-o da coleção
                unset($mangas[$mangaIndex]);
                $mangas = array_values($mangas); // Reindexa o array
                $message = 'Mangá removido da coleção';
                $added = false; // Retorno para remoção
            } else {
                // Mangá não encontrado, adiciona-o à coleção
                $mangas[] = [
                    'id' => $mangaId,
                    'name' => $mangaName,
                    'capa' => $mangaCapa,
                ];
                $message = 'Mangá adicionado à coleção';
                $added = true; // Retorno para adição
            }

            // Atualiza a coleção com o novo array de mangas
            $collection->mangas_id = json_encode($mangas);
            $collection->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $added, // Retorna true se o mangá foi adicionado e false se foi removido
                'collection' => $collection,
                'message' => $message,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar a coleção',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

}
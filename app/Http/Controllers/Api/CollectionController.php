<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\CollectionRequest;
use App\Http\Requests\MangaRequest;
use App\Models\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class CollectionController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Use o withCount para contar os likes de cada coleção
        $collections = Collection::where('user_id', $user->id)
            ->withCount('likes') // Contando os likes
            ->paginate(10);

        $collections->each(function ($collection) {
            $collection->makeHidden(['mangas_id', 'genres', 'user_id']);

            // Fazer o parse da string mangas_id em um array de objetos
            $mangasArray = json_decode($collection->mangas_id, true); // Passando true para obter um array associativo

            // Calcular o total de mangas
            $collection->total_mangas = is_array($mangasArray) ? count($mangasArray) : 0; // Verifica se é um array antes de contar
        });

        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }




    public function show($id): JsonResponse
    {
        $user = Auth::user();
    
        try {
            // Recupera a coleção com base no ID
            $collection = Collection::where('id', $id)->first();
    
            if (!$collection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Coleção não encontrada.',
                ], 404);
            }
    
            // Verifica se a coleção é privada e o usuário atual não é o dono
            if ($collection->status === 'private' && $collection->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Essa coleção é privada.',
                ], 403); // Código 403 para acesso proibido
            }
    
            // Converte mangas_id para array se não for
            $collection->mangas_id = is_string($collection->mangas_id)
                ? json_decode($collection->mangas_id, true) ?? []
                : $collection->mangas_id;
    
            // Adiciona a data formatada
            $collection->date = \Carbon\Carbon::parse($collection->updated_at)->diffForHumans();
    
            // Retorna os dados
            return response()->json([
                'status' => true,
                'collection' => $collection,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao recuperar a coleção.',
                'error' => $e->getMessage(),
            ], 400);
        }
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
                'status' => $request->input('status'),
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
            // Recupera a coleção do usuário
            $collection = Collection::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
    
            if (!$collection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Coleção não encontrada ou não autorizada',
                ], 404);
            }
    
            // Obtém os mangás da coleção
            $mangas = $collection->mangas_id ?? [];
            if (!is_array($mangas)) {
                $mangas = json_decode($mangas, true) ?? [];
            }
    
            $mangaId = $request->input('id');
            $mangaName = $request->input('name');
            $mangaCapa = $request->input('capa');
    
            // Verifica se o mangá já está na coleção
            $mangaIndex = false;
            if (!empty($mangas) && is_array($mangas)) {
                $mangaIndex = array_search($mangaId, array_column($mangas, 'id'));
            }
    
            if ($mangaIndex !== false) {
                // Mangá encontrado, remove-o da coleção
                unset($mangas[$mangaIndex]);
                $mangas = array_values($mangas); // Reindexa o array
                $message = 'Mangá removido da coleção';
                $added = false;
            } else {
                // Mangá não encontrado, adiciona-o à coleção
                $mangas[] = [
                    'id' => $mangaId,
                    'name' => $mangaName,
                    'capa' => $mangaCapa,
                ];
                $message = 'Mangá adicionado à coleção';
                $added = true;
            }
    
            // Atualiza a coleção
            $collection->mangas_id = json_encode($mangas);
            $collection->save();
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'data' => $added,
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
    
    public function toggleFixed($id): JsonResponse
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            // Recupera a coleção com base no ID e usuário autenticado
            $collection = Collection::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Alterna o valor de 'fixed' entre true e false
            $collection->fixed = !$collection->fixed;

            // Salva a atualização da coleção
            $collection->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'fixed' => $collection->fixed, // Retorna o novo valor de 'fixed'
                'message' => 'O valor de fixed foi alterado com sucesso.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Coleção não encontrada ou não autorizada.',
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao atualizar a coleção.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    public function search($search): JsonResponse
    {
        $user = Auth::user();

        $collections = Collection::where('user_id', $user->id)
            ->where('name', 'like', '%' . $search . '%') // Pesquisa parcial no nome
            ->paginate(10);

        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }

    public function userSingleCollections($id): JsonResponse
    {
        // Obtém as coleções do usuário pelo ID fornecido e com status 'public'
        $collections = Collection::where('user_id', $id)
            ->where('status', 'public') // Filtra coleções que têm o status 'public'
            ->paginate(10);

        // Remove campos indesejados de cada coleção
        $collections->each(function ($collection) {
            $collection->makeHidden(['mangas_id', 'genres', 'user_id']);
        });

        // Verifica se há coleções públicas para o usuário
        if ($collections->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Nenhuma coleção pública encontrada para este usuário.',
            ], 404);
        }

        // Retorna as coleções públicas do usuário
        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }


    public function includes(Request $request): JsonResponse
    {
        $user = Auth::user();
        $mangaId = $request->input('manga_id'); // Obtém o ID do mangá do request
        $collections = Collection::where('user_id', $user->id)->paginate(10);

        $collections->each(function ($collection) use ($mangaId) {
            $collection->makeHidden(['mangas_id', 'genres', 'user_id']);

            // Fazer o parse da string mangas_id em um array de objetos
            $mangasArray = is_string($collection->mangas_id) ? json_decode($collection->mangas_id, true) : $collection->mangas_id; // Passando true para obter um array associativo

            // Calcular o total de mangas
            $collection->total_mangas = is_array($mangasArray) ? count($mangasArray) : 0; // Verifica se é um array antes de contar

            // Adiciona o campo 'included' verificando se o mangá está na coleção
            $collection->included = false; // Valor padrão

            if (is_array($mangasArray)) {
                // Verifica se o mangá está na coleção
                $collection->included = in_array($mangaId, array_column($mangasArray, 'id'));
            }
        });

        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }

    public function toggleLike($id): JsonResponse
    {
        $user = Auth::user(); // Obtém o usuário autenticado

        DB::beginTransaction();

        try {
            // Verifica se a coleção existe
            $collection = Collection::find($id);

            if (!$collection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Coleção não encontrada.',
                ], 404);
            }

            // Procura se já existe um like do usuário para essa coleção
            $existingLike = DB::table('collections_likes')
                ->where('collection_id', $id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {
                // Remover o like existente
                DB::table('collections_likes')
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
                DB::table('collections_likes')->insert([
                    'collection_id' => $id,
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

    public function mostLikedCollections(): JsonResponse
    {
        // Obtém as coleções públicas e conta as curtidas
        $collections = Collection::where('status', 'public')
            ->withCount('likes')  // Conta as curtidas associadas à coleção
            ->orderByDesc('likes_count')  // Ordena pelas coleções mais curtidas (likes_count)
            ->paginate(10); // Paginação para limitar o número de coleções

        $collections->makeHidden(['mangas_id', 'genres']);

        // Retorna a resposta com as coleções e o likes_count
        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }


    public function uploadCover(Request $request, $collectionId)
    {
        // Validar se a imagem foi enviada
        $request->validate([
            'image' => 'required|string', // Certifique-se de que a imagem é uma string
        ]);

        // Extrair a string base64
        $imageData = $request->input('image');

        // Remover o prefixo "data:image/jpeg;base64," (ou o prefixo de qualquer outro tipo)
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);

        // Decodificar a base64 para binário
        $imageData = base64_decode($imageData);

        // Gerar um nome único para a imagem
        $fileName = uniqid() . '.jpg';

        // Salvar a imagem na pasta 'public/assets'
        $path = 'assets/' . $fileName;

        Storage::disk('public')->put($path, $imageData);

        // Obter o link completo da imagem, usando o link simbólico
        $imageUrl = Storage::url($path);  // Gera a URL pública da imagem

        // Concatenar a URL do domínio com o caminho da imagem
        $imageUrl = 'https://s2mangas.com' . $imageUrl;  // Use o caminho correto para o domínio

        // Atualizar a coleção com o novo caminho da capa
        $collection = Collection::findOrFail($collectionId);
        $collection->capa = $imageUrl;  // Atualiza com o link completo da imagem
        $collection->save();

        return response()->json([
            'status' => true,
            'message' => 'Capa da coleção atualizada com sucesso!',
            'capa' => $imageUrl,  // Retorna a URL completa da imagem
        ]);
    }


    public function searchAll($search): JsonResponse
    {
        $collections = Collection::withCount('likes')
            ->where('status', 'public') // Filtra por status "public"
            ->where('name', 'like', '%' . $search . '%') // Pesquisa parcial no nome
            ->paginate(10);

        $response = $collections->isEmpty()
            ? []
            : $collections;


        return response()->json([
            'status' => true,
            'collections' => $response,
        ], 200);
    }
    public function userCollections(): JsonResponse
    {
        $user = Auth::user();
    
        // Obtém as coleções do usuário pelo ID fornecido e ordena pela propriedade 'fixed'
        $collections = Collection::where('user_id', $user->id)
            ->orderByDesc('fixed') // Ordena pela propriedade 'fixed' primeiro
            ->paginate(20);
    
        $collections->each(function ($collection) {
            // Garante que mangas_id seja tratado como array
            $mangas = $collection->mangas_id ?? []; // Inicializa como array vazio se for null
    
            if (is_string($mangas)) {
                $mangas = json_decode($mangas, true) ?? []; // Decodifica para array; fallback para array vazio
            }
    
            if (!is_array($mangas)) {
                $mangas = []; // Garante que é sempre um array
            }
    
            // Oculta os campos desnecessários na resposta
            $collection->makeHidden(['mangas_id', 'genres', 'user_id', 'created_at', 'updated_at']);
    
            // Calcula o total de mangás
            $collection->total_mangas = count($mangas);
    
            // Adiciona o campo 'update_date' formatado com Carbon
            $collection->date = \Carbon\Carbon::parse($collection->updated_at)->diffForHumans();
        });
    
        // Verifica se há coleções públicas para o usuário
        if ($collections->isEmpty()) {
            return response()->json([
                'status' => false,
                'collections' => [],
                'message' => 'Nenhuma coleção pública encontrada para este usuário.',
            ], 404);
        }
    
        // Retorna as coleções públicas do usuário
        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }
    

}
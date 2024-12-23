<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\FeedRequest;
use App\Models\Feed;
use App\Models\MangaItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FeedController extends Controller
{
    /**
     * Exibir todos os feeds.
     */
    public function index()
    {
        $feeds = Feed::all();

        // Carregar os mangas para cada feed
        foreach ($feeds as $feed) {
            $feed->mangas = MangaItem::whereIn('id', json_decode($feed->manga_ids))->get();
        }

        return response()->json([
            'status' => true,
            'feeds' => $feeds,
        ]);
    }

    /**
     * Criar um novo feed.
     */
    public function store(FeedRequest $request)
    {
        // Validação dos dados
        $validated = $request->validated();
    
        // Garantir que manga_ids seja um JSON válido
        if (isset($validated['manga_ids'])) {
            // Salvar manga_ids como JSON
            $validated['manga_ids'] = json_encode($validated['manga_ids']);
        }
    
        // Criar o feed
        $feed = Feed::create($validated);
    
        return response()->json([
            'status' => true,
            'message' => 'Feed created successfully',
            'feed' => $feed,
        ]);
    }
    
    /**
     * Exibir um feed específico.
     */
    public function show($id)
    {
        $feed = Feed::find($id);

        if (!$feed) {
            return response()->json([
                'status' => false,
                'message' => 'Feed not found',
            ], 404);
        }

        // Carregar os mangas associados
        $feed->mangas = MangaItem::whereIn('id', json_decode($feed->manga_ids))->get();

        return response()->json([
            'status' => true,
            'feed' => $feed,
        ]);
    }

    /**
     * Atualizar um feed.
     */
    public function update(FeedRequest $request, $id)
    {
        $feed = Feed::find($id);
    
        if (!$feed) {
            return response()->json([
                'status' => false,
                'message' => 'Feed not found',
            ], 404);
        }
    
        // O request já foi validado pelo StoreFeedRequest
        $validated = $request->validated();
    
        // Se manga_ids foi atualizado, converte para JSON
        if (isset($validated['manga_ids'])) {
            $validated['manga_ids'] = json_encode($validated['manga_ids']);
        }
    
        // Atualizar o feed
        $feed->update($validated);
    
        return response()->json([
            'status' => true,
            'message' => 'Feed updated successfully',
            'feed' => $feed,
        ]);
    }

    /**
     * Deletar um feed.
     */
    public function destroy($id)
    {
        $feed = Feed::find($id);

        if (!$feed) {
            return response()->json([
                'status' => false,
                'message' => 'Feed not found',
            ], 404);
        }

        // Deletar o feed
        $feed->delete();

        return response()->json([
            'status' => true,
            'message' => 'Feed deleted successfully',
        ]);
    }
}

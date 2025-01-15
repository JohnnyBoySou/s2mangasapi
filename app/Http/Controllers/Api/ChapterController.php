<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class ChapterController extends Controller
{
    public function listChapters($id, Request $request)
    {
        $lg = $request->query('lang', 'pt-br'); // Idioma padrão
        $order = $request->query('order', 'desc'); // Ordem dos capítulos
        $page = $request->query('page', 0); // Página padrão
        $limit = $request->query('limit', 20);
        $offset = $page * $limit;

        try {
            // Chamada à API externa
            $response = Http::get("https://api.mangadex.org/manga/{$id}/feed?includeEmptyPages=0&includeFuturePublishAt=0&includeExternalUrl=0", [
                'limit' => $limit,
                'offset' => $offset,
                'translatedLanguage' => [$lg],
                'contentRating' => ['safe', 'suggestive', 'erotica', 'pornographic'],
                'order' => [
                    'chapter' => $order,
                ],
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Failed to fetch chapters'], 500);
            }

            // Extração dos dados
            $data = $response->json('data');
            $total = $response->json('total'); // Total de capítulos vindo da API externa

            // Transformar os dados do capítulo
            $result = $this->transformChapterData($data, $total, $limit, $page, $offset, $lg);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function transformChapterData($chapters, $total, $limit, $page, $offset, $lg)
    {
        $transformedChapters = is_array($chapters) ? array_filter(array_map(function ($chapter) {
            $attributes = $chapter['attributes'] ?? [];

            $publishDate = \Carbon\Carbon::parse($attributes['publishAt'] ?? now())
            ->locale('pt-BR')
            ->isoFormat('D MMM YYYY');

            $pages = $attributes['pages'] ?? 0;

            if ($pages == 0) {
            return null;
            }

            return [
            'id' => $chapter['id'] ?? null,
            'title' => $attributes['title'] ?? 'Capítulo ' . ($attributes['chapter'] ?? 0),
            'chapter' => isset($attributes['chapter']) ? (float) $attributes['chapter'] : 0,
            'volume' => isset($attributes['volume']) ? (float) $attributes['volume'] : null,
            'language' => [$attributes['translatedLanguage'] ?? ''],
            'publish_date' => $publishDate,
            'pages' => $pages,
            ];
        }, $chapters)) : [];
        $transformedChapters = array_values($transformedChapters);
        return [
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'lg' => $lg,
            'chapters' => $transformedChapters,
        ];
    }

    public function listPages($chapterID, Request $request)
    {
        try {
            $baseUrl = 'https://api.mangadex.org'; // Defina o URL base da API

            // Fazer requisição para obter o capítulo
            $chapterResponse = Http::get("{$baseUrl}/at-home/server/{$chapterID}");

            // Verificar se a requisição falhou
            if ($chapterResponse->failed()) {
                return response()->json(['error' => 'Failed to fetch chapter'], 500);
            }

            $chapterData = $chapterResponse->json('chapter');

            // Verificar se os dados do capítulo são válidos
            if (!$chapterData || !isset($chapterData['hash']) || !isset($chapterData['data'])) {
                return response()->json(['error' => 'Invalid chapter data'], 500);
            }

            // Transformar as páginas
            $pages = $this->transformPage($chapterData['hash'], $chapterData['data']);

            // Retornar as páginas
            return response()->json([
                'pages' => $pages,
                'total' => count($pages),
                'chapter_id' => $chapterID,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Função para transformar as páginas
    private function transformPage($chapterHash, $pageData, $baseUrl = "https://uploads.mangadex.org")
    {
        if (!$chapterHash || empty($pageData)) {
            throw new \Exception("Invalid chapter data");
        }

        return array_map(function ($page) use ($chapterHash, $baseUrl) {
            return "{$baseUrl}/data/{$chapterHash}/{$page}";
        }, $pageData);
    }
}

<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\MangaRequest;
use App\Models\MangaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MangadexController extends Controller
{

    public function search($mangaID)
    {
        try {
            $baseURL = 'https://api.mangadex.org';
            $lg = request()->query('lang', 'pt-br');
            $response = Http::get("$baseURL/manga/$mangaID", [
                'translatedLanguage' => [$lg],
                'includes' => ['cover_art']
            ]);
            $mangaData = $response->json();
            $statsResponse = Http::get("$baseURL/statistics/manga/$mangaID");
            $mangaStats = $statsResponse->json();
            $data = $this->transformData(
                $mangaData['data'] ?? null,
                $mangaStats,
            );
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao obter informações do mangá.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchByName($name, $page = 0)
    {
        try {
            $baseURL = 'https://api.mangadex.org';
            $lg = request()->query('lang', 'pt-br');
            $response = Http::get("$baseURL/manga", [
                'title' => $name,
                'translatedLanguage' => [$lg],
                'includes' => ['cover_art'],
                'offset' => 20 * $page
            ]);
            $mangaData = $response->json();
            $mangaID = $mangaData['data'][0]['id'] ?? null;

            if (!$mangaID) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mangá não encontrado.',
                    'name' => $name,
                ], 404);
            }

            $statsResponse = Http::get("$baseURL/statistics/manga/$mangaID");
            $mangaStats = $statsResponse->json();
            $data = $this->transformData(
                $mangaData['data'][0] ?? null,
                $mangaStats,
            );
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao obter informações do mangá.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transformData($data, $stats, )
    {
        if (!$data) {
            return null;
        }

        $id = $data['id'];
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        $statistics = $stats['statistics'][$id] ?? [];

        $statusMapping = [
            'completed' => 'Completo',
            'ongoing' => 'Em andamento',
            'hiatus' => 'Hiato',
            'cancelled' => 'Cancelado'
        ];

        $ratingMapping = [
            'safe' => false,
            'suggestive' => true,
            'erotica' => true,
            'pornographic' => true
        ];

        // Processa descrições e categorias
        $descriptions = [];

        if (isset($attributes['description']) && is_array($attributes['description'])) {
            foreach ($attributes['description'] as $lang => $desc) {
                // Remove links do texto, garantindo que seja uma string
                $descriptions[$lang] = $this->removeLinksFromText($desc);
            }
        }
        $categories = array_map(fn($tag) => $tag['attributes']['name']['en'] ?? null, $attributes['tags'] ?? []);
        $long = in_array('Long Strip', $categories);

        // Capa
        $coverArt = collect($relationships)->firstWhere('type', 'cover_art');
        $capa = $coverArt ? "https://uploads.mangadex.org/covers/$id/{$coverArt['attributes']['fileName']}" : '';

        // Idiomas disponíveis
        $languages = array_map(fn($lang) => [
            'id' => $lang,
            'name' => config("iso_languages.$lang", $lang),
        ], $attributes['availableTranslatedLanguages'] ?? []);

        return [
            'id' => $id,
            'name' => $attributes['title']['en'] ?? $attributes['title']['pt-br'] ?? 'Título não encontrado',
            'altTitles' => $attributes['altTitles'] ?? [],
            'capa' => $capa,
            'type' => match ($data['type']) {
                'manga' => 'Mangá',
                'manhwa' => 'Manhwa',
                'manhua' => 'Manhua',
                default => 'Mangá'
            },
            'followers' => $statistics['follows'] ?? 0,
            'description' => $descriptions, // Descrições em todos os idiomas
            'status' => $statusMapping[$attributes['status']] ?? $attributes['status'],
            'contentRating' => $attributes['contentRating'],
            'year' => $attributes['year'] ?? null,
            'categories' => $categories,
            'create_date' => date('d M Y', strtotime($attributes['createdAt'])),
            'release_date' => date('Y-m-d', strtotime($attributes['updatedAt'])),
            'languages' => $languages,
            'long' => $long,
            'originalLanguage' => $attributes['originalLanguage'],
            'lastVolume' => $attributes['lastVolume'],
            'lastChapter' => $attributes['lastChapter'],
            'publicationDemographic' => $attributes['publicationDemographic'],
        ];
    }

    public function removeLinksFromText($text)
    {
        if (!is_string($text)) {
            return '';
        }

        return preg_replace('/https?:\/\/[^\s]+/', '', $text);
    }
}
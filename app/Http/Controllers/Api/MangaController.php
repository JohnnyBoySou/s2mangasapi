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

class MangaController extends Controller
{
    // Exibir todos os mangas
    public function index()
    {
        $mangas = MangaItem::select('uuid', 'name', 'capa', 'views_count', 'id')  // Seleciona os campos desejados
        ->orderBy('created_at', 'desc')  // Ordena pela quantidade de visualizações
        ->paginate(10);  // Pagi
           
        return response()->json($mangas);
    }

    // Criar um novo manga
    public function store(MangaRequest $request)
    {
        // Verificando o usuário autenticado
        $user = Auth::user();

        // Iniciando a transação
        DB::beginTransaction();

        try {
            // Validação dos dados recebidos
            $validated = $request->validated();

            $validated['user_id'] = $user->id;
            $validated['created_by'] = $user->id;

            // Criando o manga
            $manga = MangaItem::create($validated);

            // Confirmando a transação
            DB::commit();

            return response()->json(['message' => 'Mangá criado com sucesso!', 'data' => $manga], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar manga', 'message' => $e->getMessage()], 500);
        }
    }

    // Exibir um manga específico
    public function show($id, Request $request)
    {
        $manga = MangaItem::findOrFail($id);
        $lang = $request->get('lang', default: 'en');
        $description = $manga->getDescriptionByLocale($lang);
        $manga->increment('views_count'); // Incrementa o contador de views
        $manga->description = $description;

        $mangauuid = $manga->uuid;

        // Fetch covers
        $covers = $this->getCovers($mangauuid);
        $manga->covers = $covers;

        return response()->json(
            $manga,
        );
    }

    public function getCovers($mangaID)
    {
        try {
            $response = Http::get('https://api.mangadex.org/cover', [
                'manga' => [$mangaID]
            ]);
            $data = $response->json()['data'];
            return $this->formatCoverData($data, $mangaID);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatCoverData($covers, $mangaID)
    {
        return array_map(function ($cover) use ($mangaID) {
            return [
                'img' => "https://uploads.mangadex.org/covers/{$mangaID}/{$cover['attributes']['fileName']}",
                'volume' => $cover['attributes']['volume'],
                'id' => $cover['id']
            ];
        }, $covers);
    }

    // Atualizar um manga
    public function update(MangaRequest $request, $id)
    {
        // Verificando o usuário autenticado
        $user = Auth::user();

        // Iniciando a transação
        DB::beginTransaction();

        try {
            $manga = MangaItem::findOrFail($id);

            $validated = $request->validated();

            $validated['user_id'] = $user->id;
            $validated['created_by'] = $user->id;

            // Convertendo arrays para JSON, caso presentes
            if (isset($validated['categories'])) {
                $validated['categories'] = json_encode($validated['categories']);
            }

            if (isset($validated['languages'])) {
                $validated['languages'] = json_encode($validated['languages']);
            }

            // Atribuindo o ID do usuário autenticado
            $validated['updated_by'] = $user->id;

            // Atualizando o manga
            $manga->update($validated);

            // Confirmando a transação
            DB::commit();

            return response()->json(['message' => 'Mangá atualizado com sucesso!', 'data' => $manga]);

        } catch (\Exception $e) {
            // Se houver erro, desfaz as mudanças
            DB::rollBack();

            return response()->json(['error' => 'Erro ao atualizar manga', 'message' => $e->getMessage()], 500);
        }
    }

    // Deletar um manga
    public function destroy($id)
    {
        // Iniciando a transação
        DB::beginTransaction();

        try {
            $manga = MangaItem::findOrFail($id);
            $manga->delete();

            // Confirmando a transação
            DB::commit();

            return response()->json(['message' => 'Manga deletado com sucesso']);

        } catch (\Exception $e) {
            // Se houver erro, desfaz a transação
            DB::rollBack();

            return response()->json(['error' => 'Erro ao deletar manga', 'message' => $e->getMessage()], 500);
        }
    }
    //PESQUISAR
    public function search(Request $request)
    {
        // Inicia a query com o modelo MangaItem
        /*

        $results = MangaItem::select('uuid', 'name', 'capa', 'views_count')
            ->orderBy('views_count', 'desc')
            ->paginate(10);

        return response()->json($results);
        */

        $query = MangaItem::query();

        // Verificar se o nome foi enviado
        if ($request->has('name') && !empty($request->input('name'))) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // Verificar se a categoria foi enviada
        if ($request->has('category') && !empty($request->input('category'))) {
            $query->whereJsonContains('categories', $request->input('category'));
        }

        // Verificar se o status foi enviado
        if ($request->has('status') && !empty($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        // Seleciona apenas as colunas desejadas e adiciona paginação
        $results = $query->select('uuid', 'name', 'capa', 'views_count', 'id')
            ->orderBy('views_count', 'desc') // Ordena pela quantidade de visualizações
            ->paginate(10); // Pagina com 10 itens por página

        if ($results->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Nenhum resultado encontrado.',
                'name' => $request->input('name'),
                'data' => [],
            ], 200);
        }
        // Retorna a resposta como JSON
        return response()->json([
            'status' => true,
            'message' => 'Resultados encontrados.',
            'data' => $results,
        ], 200);
    }

    //CURTIR
    public function like($id): JsonResponse
    {
        $user = Auth::user();

        DB::beginTransaction();

        try {
            $manga = MangaItem::find($id);

            if (!$manga) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mangá não encontrado.',
                ], 404);
            }

            // Procura se já existe um like do usuário para esse mangá
            $existingLike = DB::table('mangas_likes')
                ->where('manga_id', $id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {
                // Remover o like existente
                DB::table('mangas_likes')
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
                DB::table('mangas_likes')->insert([
                    'manga_id' => $id,
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Erro ao alternar curtida.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    //ESTATISTICAS
    public function statistics(): JsonResponse
    {
        $currentWeekStart = now()->startOfWeek();
        $currentWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $currentYearStart = now()->startOfYear();
        $currentYearEnd = now()->endOfYear();
        $lastYearStart = now()->subYear()->startOfYear();
        $lastYearEnd = now()->subYear()->endOfYear();

        // Quantidade de Mangas
        $publishedLastWeek = MangaItem::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();
        $publishedThisWeek = MangaItem::whereBetween('created_at', [$currentWeekStart, $currentWeekEnd])->count();
        $publishedThisMonth = MangaItem::whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])->count();
        $publishedLastMonth = MangaItem::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $publishedThisYear = MangaItem::whereBetween('created_at', [$currentYearStart, $currentYearEnd])->count();
        $publishedLastYear = MangaItem::whereBetween('created_at', [$lastYearStart, $lastYearEnd])->count();

        // Porcentagem de aumento ou queda nos mangás semanais
        $weekComparison = $publishedLastWeek > 0
            ? round((($publishedThisWeek - $publishedLastWeek) / $publishedLastWeek) * 100, 2)
            : ($publishedThisWeek > 0 ? 100 : 0);

        // Porcentagem de aumento ou queda nos mangás mensais
        $monthComparison = $publishedLastMonth > 0
            ? round((($publishedThisMonth - $publishedLastMonth) / $publishedLastMonth) * 100, 2)
            : ($publishedThisMonth > 0 ? 100 : 0);

        // Porcentagem de aumento ou queda nos mangás anuais
        $yearComparison = $publishedLastYear > 0
            ? round((($publishedThisYear - $publishedLastYear) / $publishedLastYear) * 100, 2)
            : ($publishedThisYear > 0 ? 100 : 0);

        // Quantidade de likes
        $likesLastWeek = DB::table('mangas_likes')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->count();
        $likesThisWeek = DB::table('mangas_likes')
            ->whereBetween('created_at', [$currentWeekStart, $currentWeekEnd])
            ->count();
        $likesThisMonth = DB::table('mangas_likes')
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();
        $likesLastMonth = DB::table('mangas_likes')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $likesThisYear = DB::table('mangas_likes')
            ->whereBetween('created_at', [$currentYearStart, $currentYearEnd])
            ->count();
        $likesLastYear = DB::table('mangas_likes')
            ->whereBetween('created_at', [$lastYearStart, $lastYearEnd])
            ->count();

        // Porcentagem de aumento ou queda nos likes semanais
        $likesWeekComparison = $likesLastWeek > 0
            ? round((($likesThisWeek - $likesLastWeek) / $likesLastWeek) * 100, 2)
            : ($likesThisWeek > 0 ? 100 : 0);

        // Porcentagem de aumento ou queda nos likes mensais
        $likesMonthComparison = $likesLastMonth > 0
            ? round((($likesThisMonth - $likesLastMonth) / $likesLastMonth) * 100, 2)
            : ($likesThisMonth > 0 ? 100 : 0);

        // Porcentagem de aumento ou queda nos likes anuais
        $likesYearComparison = $likesLastYear > 0
            ? round((($likesThisYear - $likesLastYear) / $likesLastYear) * 100, 2)
            : ($likesThisYear > 0 ? 100 : 0);

        // Retorno das estatísticas
        return response()->json([
            'status' => true,
            'statistics' => [
                'mangas' => [
                    'last_week' => $publishedLastWeek,
                    'this_week' => $publishedThisWeek,
                    'this_month' => $publishedThisMonth,
                    'last_month' => $publishedLastMonth,
                    'this_year' => $publishedThisYear,
                    'last_year' => $publishedLastYear,
                    'week_comparison' => $weekComparison, // % de aumento ou queda
                    'month_comparison' => $monthComparison, // % de aumento ou queda
                    'year_comparison' => $yearComparison, // % de aumento ou queda
                ],
                'likes' => [
                    'last_week' => $likesLastWeek,
                    'this_week' => $likesThisWeek,
                    'this_month' => $likesThisMonth,
                    'last_month' => $likesLastMonth,
                    'this_year' => $likesThisYear,
                    'last_year' => $likesLastYear,
                    'week_comparison' => $likesWeekComparison, // % de aumento ou queda
                    'month_comparison' => $likesMonthComparison, // % de aumento ou queda
                    'year_comparison' => $likesYearComparison, // % de aumento ou queda
                ],
            ],
        ], 200);
    }

    public function user(): JsonResponse
    {
        $user = Auth::user();

        // Busca os mangás do usuário com paginação
        $mangas = MangaItem::where('user_id', $user->id)->paginate(10);

        return response()->json([
            'status' => true,
            'mangas' => $mangas,
        ], 200);
    }

    public function views($id): JsonResponse
    {
        // Validar se o mangá existe
        $manga = MangaItem::findOrFail($id);

        // Períodos
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfYear = now()->startOfYear();
        $endOfYear = now()->endOfYear();

        // Consultar visualizações detalhadas
        $viewsPerWeek = DB::table('manga_views')
            ->where('manga_id', $id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $viewsPerMonth = DB::table('manga_views')
            ->where('manga_id', $id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $viewsPerYear = DB::table('manga_views')
            ->where('manga_id', $id)
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->count();

        // Retornar resposta JSON
        return response()->json([
            'status' => true,
            'manga_id' => $id,
            'manga_name' => $manga->name,
            'views' => [
                'this_week' => $viewsPerWeek,
                'this_month' => $viewsPerMonth,
                'this_year' => $viewsPerYear,
            ],
        ]);
    }

    public function top()
    {
        // Paginação com 10 itens por página
        $mangas = MangaItem::select('uuid', 'name', 'capa', 'views_count', 'id')
            ->orderBy('views_count', 'desc')  // Ordena pela quantidade de visualizações
            ->paginate(10);  // Pagina com 10 mangás por página

        return response()->json($mangas);
    }
    // Listar os mangás mais visualizados na semana
    public function weekend()
    {
        $oneWeekAgo = Carbon::now()->subWeek();

        // Paginação com 10 itens por página
        $mangas = MangaItem::select('uuid', 'name', 'capa', 'views_count', 'id')
            ->where('updated_at', '>=', $oneWeekAgo)
            ->orderBy('views_count', 'desc')
            ->paginate(10);  // Pagina com 10 mangás por página

        return response()->json($mangas);
    }
    public function new()
    {
        // Paginação com 10 itens por página
        $mangas = MangaItem::select('uuid', 'name', 'capa', 'views_count', 'id')  // Seleciona os campos desejados
            ->orderBy('created_at', 'desc')  // Ordena pelos mais recentes
            ->paginate(10);  // Pagina com 10 mangás por página

        return response()->json($mangas);
    }
    public function feed()
    {
        $user = Auth::user();

        // Recupera as categorias favoritas do usuário
        $userCategories = $user->genres; // Se o campo agora é `categories`, certifique-se de ajustá-lo aqui também

        // Verifica se o usuário tem categorias favoritas
        if (empty($userCategories)) {
            return response()->json([
                'status' => false,
                'message' => 'Nenhuma categoria favorita encontrada para este usuário.',
            ], 404);
        }

        // Filtra os mangás com base nas categorias favoritas
        $mangas = MangaItem::select('uuid', 'name', 'capa', 'views_count', 'id')
            ->where(function ($query) use ($userCategories) {
                foreach ($userCategories as $category) {
                    $query->orWhereJsonContains('categories', $category);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($mangas);
    }







}

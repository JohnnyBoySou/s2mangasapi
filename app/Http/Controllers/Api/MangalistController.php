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
        $mangalist = MangaList::find($id); // Busca o mangalist diretamente pelo ID

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
    public function like($id): JsonResponse
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
    public function user(): JsonResponse
    {
        $user = Auth::user();

        $mangalist = Mangalist::where('user_id', $user->id)->paginate(10);

        return response()->json([
            'status' => true,
            'mangalist' => $mangalist,
        ], 200);
    }
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

    // Quantidade de Mangalists
    $publishedLastWeek = Mangalist::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();
    $publishedThisWeek = Mangalist::whereBetween('created_at', [$currentWeekStart, $currentWeekEnd])->count();
    $publishedThisMonth = Mangalist::whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])->count();
    $publishedLastMonth = Mangalist::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
    $publishedThisYear = Mangalist::whereBetween('created_at', [$currentYearStart, $currentYearEnd])->count();
    $publishedLastYear = Mangalist::whereBetween('created_at', [$lastYearStart, $lastYearEnd])->count();

    // Porcentagem de aumento ou queda nos mangalists anuais
    $yearComparison = $publishedLastYear > 0
        ? round((($publishedThisYear - $publishedLastYear) / $publishedLastYear) * 100, 2)
        : ($publishedThisYear > 0 ? 100 : 0);

    // Porcentagem de aumento ou queda nos mangalists semanais
    $weekComparison = $publishedLastWeek > 0
        ? round((($publishedThisWeek - $publishedLastWeek) / $publishedLastWeek) * 100, 2)
        : ($publishedThisWeek > 0 ? 100 : 0);

    // Porcentagem de aumento ou queda nos mangalists mensais
    $monthComparison = $publishedLastMonth > 0
        ? round((($publishedThisMonth - $publishedLastMonth) / $publishedLastMonth) * 100, 2)
        : ($publishedThisMonth > 0 ? 100 : 0);

    // Quantidade de likes
    $likesLastWeek = DB::table('mangalists_likes')
        ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
        ->count();
    $likesThisWeek = DB::table('mangalists_likes')
        ->whereBetween('created_at', [$currentWeekStart, $currentWeekEnd])
        ->count();
    $likesThisMonth = DB::table('mangalists_likes')
        ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
        ->count();
    $likesLastMonth = DB::table('mangalists_likes')
        ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
        ->count();
    $likesThisYear = DB::table('mangalists_likes')
        ->whereBetween('created_at', [$currentYearStart, $currentYearEnd])
        ->count();
    $likesLastYear = DB::table('mangalists_likes')
        ->whereBetween('created_at', [$lastYearStart, $lastYearEnd])
        ->count();

    // Porcentagem de aumento ou queda nos likes anuais
    $likesYearComparison = $likesLastYear > 0
        ? round((($likesThisYear - $likesLastYear) / $likesLastYear) * 100, 2)
        : ($likesThisYear > 0 ? 100 : 0);

    // Porcentagem de aumento ou queda nos likes semanais
    $likesWeekComparison = $likesLastWeek > 0
        ? round((($likesThisWeek - $likesLastWeek) / $likesLastWeek) * 100, 2)
        : ($likesThisWeek > 0 ? 100 : 0);

    // Porcentagem de aumento ou queda nos likes mensais
    $likesMonthComparison = $likesLastMonth > 0
        ? round((($likesThisMonth - $likesLastMonth) / $likesLastMonth) * 100, 2)
        : ($likesThisMonth > 0 ? 100 : 0);

    // Retorno das estatísticas
    return response()->json([
        'status' => true,
        'statistics' => [
            'mangalists' => [
                'last_week' => $publishedLastWeek,
                'this_week' => $publishedThisWeek,
                'this_month' => $publishedThisMonth,
                'last_month' => $publishedLastMonth,
                'this_year' => $publishedThisYear,
                'last_year' => $publishedLastYear,
                'week_comparison' => $weekComparison, // % de aumento ou queda semanal
                'month_comparison' => $monthComparison, // % de aumento ou queda mensal
                'year_comparison' => $yearComparison, // % de aumento ou queda anual
            ],
            'likes' => [
                'last_week' => $likesLastWeek,
                'this_week' => $likesThisWeek,
                'this_month' => $likesThisMonth,
                'last_month' => $likesLastMonth,
                'this_year' => $likesThisYear,
                'last_year' => $likesLastYear,
                'week_comparison' => $likesWeekComparison, // % de aumento ou queda semanal
                'month_comparison' => $likesMonthComparison, // % de aumento ou queda mensal
                'year_comparison' => $likesYearComparison, // % de aumento ou queda anual
            ],
        ],
    ], 200);
}
    
}

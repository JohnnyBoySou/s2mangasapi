<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\MangaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MangaController extends Controller
{
    // Exibir todos os mangas
    public function index()
    {
        $mangas = MangaItem::all();
        return response()->json($mangas);
    }

    // Criar um novo manga
    public function store(Request $request)
    {
        // Verificando o usuário autenticado
        $user = Auth::user();

        // Iniciando a transação para garantir que a operação seja atômica
        DB::beginTransaction();

        try {
            // Validação dos dados
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'capa' => 'required|string',
                'categories' => 'required|array',
                'languages' => 'required|array',
                'release_date' => 'required|date',
                'status' => 'required|string',
                'type' => 'required|string|in:Mangá,Light Novel,Manhwa',
                'year' => 'required|integer',
            ]);

            // Criando o novo manga
            $manga = MangaItem::create($validated);

            // Se necessário, você pode fazer associações adicionais aqui, por exemplo:
            // $manga->user()->associate($user);
            // $manga->save();

            // Confirmando a transação
            DB::commit();

            return response()->json($manga, 201); // Retorna o manga criado com status 201

        } catch (\Exception $e) {
            // Se houver um erro, desfaz as alterações realizadas até agora
            DB::rollBack();

            // Retorna o erro com mensagem
            return response()->json(['error' => 'Erro ao criar manga', 'message' => $e->getMessage()], 500);
        }
    }

    // Exibir um manga específico
    public function show($id)
    {
        $manga = MangaItem::findOrFail($id);
        return response()->json($manga);
    }

    // Atualizar um manga
    public function update(Request $request, $id)
    {
        // Verificando o usuário autenticado
        $user = Auth::user();

        // Iniciando a transação
        DB::beginTransaction();

        try {
            $manga = MangaItem::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'capa' => 'sometimes|string',
                'categories' => 'sometimes|array',
                'languages' => 'sometimes|array',
                'release_date' => 'sometimes|date',
                'status' => 'sometimes|string',
                'type' => 'sometimes|string|in:Mangá,Light Novel,Manhwa',
                'year' => 'sometimes|integer',
            ]);

            // Atualizando o manga
            $manga->update($validated);

            // Confirmando a transação
            DB::commit();

            return response()->json($manga);

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
}

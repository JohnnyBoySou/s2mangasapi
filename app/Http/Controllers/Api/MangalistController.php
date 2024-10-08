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
        $user = Auth::user();
        $mangalist = Mangalist::where('user_id', $user->id)->paginate(10);
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

}

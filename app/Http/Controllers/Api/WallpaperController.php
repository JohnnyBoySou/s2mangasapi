<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WallpaperRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallpaper;

class WallpaperController extends Controller
{
    // Lista todos os wallpapers sem o campo 'data'
    public function index()
    {
        $wallpapers = Wallpaper::orderBy('created_at', 'desc')->paginate(10)->makeHidden('data');
        return response()->json($wallpapers, 200);
    }

    // Cria um novo wallpaper
    public function store(WallpaperRequest $request)
    {
        $data = $request->validated();

        // Adiciona o `user_id` automaticamente
        $data['user_id'] = Auth::id();

        $wallpaper = Wallpaper::create($data);

        return response()->json([
            'status' => true,
            'wallpaper' => $wallpaper,
        ], 201);
    }


    // Mostra um wallpaper específico
    public function show($id)
    {
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper not found'], 404);
        }

        // Ensure 'data' is cast to array, even if it's empty
        $wallpaper->data = is_string($wallpaper->data) ? json_decode($wallpaper->data, true) : $wallpaper->data;

        return response()->json($wallpaper, 200);
    }

    // Atualiza um wallpaper
    public function update(WallpaperRequest $request, $id)
    {
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper não encontrado'], 404);
        }

        $validatedData = $request->validated();

        $wallpaper->update($validatedData);

        return response()->json(['message' => 'Wallpaper atualizado com sucesso', 'data' => $wallpaper], 200);
    }


    // Deleta um wallpaper
    public function destroy($id)
    {
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper not found'], 404);
        }

        $wallpaper->delete();

        return response()->json(['message' => 'Wallpaper deleted'], 200);
    }

    public function remove(WallpaperRequest $request, $id)
    {
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper não encontrado'], 404);
        }

        // Validar os dados recebidos e garantir que o campo 'img' foi enviado
        $validatedData = $request->validate([
            'img' => 'required|url', // Certifica-se de que o campo 'img' é obrigatório e deve ser uma URL válida
        ]);

        // Verificar se o campo 'img' foi enviado
        $imgUrl = $validatedData['img'];

        // Decodificar o campo `data` do banco de dados
        $data = json_decode($wallpaper->data, true);

        // Verificar se o campo `data` contém um array válido
        if (!is_array($data)) {
            return response()->json(['message' => 'O campo data não contém um array válido'], 400);
        }

        // Filtrar os itens que não correspondem ao `img` enviado
        $filteredData = array_filter($data, function ($item) use ($imgUrl) {
            // Verificar se o campo 'img' existe e se é diferente do valor fornecido
            return isset($item['img']) && $item['img'] !== $imgUrl;
        });

        // Verificar se algum item foi removido
        if (count($data) === count($filteredData)) {
            return response()->json(['message' => 'Imagem não encontrada no campo data'], 404);
        }

        // Atualizar o campo `data` no banco de dados com os valores filtrados
        // Re-encode o array de volta para o formato JSON
        $wallpaper->data = json_encode(array_values($filteredData));
        $wallpaper->save();

        // Retornar a resposta JSON com o estado atualizado
        return response()->json([
            'message' => 'Imagem removida com sucesso',
            'data' => [
                'id' => $wallpaper->id,
                'name' => $wallpaper->name,
                'capa' => $wallpaper->capa,
                'data' => $filteredData,
            ],
        ], 200);
    }

    public function add(WallpaperRequest $request, $id)
    {
        // Buscar o wallpaper no banco
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper não encontrado'], 404);
        }

        // Validar o campo `img` que será enviado na requisição
        $validatedData = $request->validate([
            'img' => 'required|url', // Certificar-se de que `img` é uma URL válida
        ]);

        $imgUrl = $validatedData['img'];

        // Decodificar o campo `data` (que contém as URLs das imagens)
        $data = json_decode($wallpaper->data, true);

        // Verificar se o campo `data` é um array válido
        if (!is_array($data)) {
            return response()->json(['message' => 'O campo data não contém um array válido'], 400);
        }

        // Verificar se a imagem já existe no campo `data`
        foreach ($data as $item) {
            if (isset($item['img']) && $item['img'] === $imgUrl) {
                return response()->json(['message' => 'A imagem já existe no campo data'], 400);
            }
        }

        // Adicionar a nova imagem ao array `data`
        $data[] = ['img' => $imgUrl];

        // Atualizar o campo `data` com a nova imagem
        $wallpaper->data = json_encode($data);
        $wallpaper->save();

        // Retornar a resposta com o novo estado do wallpaper
        return response()->json([
            'message' => 'Imagem adicionada com sucesso',
            'data' => [
                'id' => $wallpaper->id,
                'name' => $wallpaper->name,
                'capa' => $wallpaper->capa,
                'data' => $data,
            ],
        ], 200);
    }

    public function user()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }

        $wallpapers = Wallpaper::where('user_id', $user->id)->paginate(10);

        return response()->json([
            'status' => true,
            'wallpapers' => $wallpapers,
        ], 200);
    }

}

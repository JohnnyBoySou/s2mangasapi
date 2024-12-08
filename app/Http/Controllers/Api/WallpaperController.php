<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallpaper;

class WallpaperController extends Controller
{
    // Lista todos os wallpapers
    public function index()
    {
        return response()->json(Wallpaper::all(), 200);
    }

    // Cria um novo wallpaper
    public function store(Request $request)
    {
        // Validação dos dados recebidos
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'capa' => 'required|url|max:2083',
            'data' => 'required|array', // Verifica se 'data' é um array
            'data.*.img' => 'required|url', // Valida cada item no array de 'data'
        ]);
    
        // Transformar o campo 'data' em JSON
        $validatedData['data'] = json_encode($validatedData['data']);
    
        // Criar o registro no banco de dados
        $wallpaper = Wallpaper::create($validatedData);
    
        // Retornar a resposta com status 201
        return response()->json($wallpaper, 201);
    }
    
    // Mostra um wallpaper específico
    public function show($id)
    {
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper not found'], 404);
        }

        return response()->json($wallpaper, 200);
    }

    // Atualiza um wallpaper
    public function update(Request $request, $id)
    {
        $wallpaper = Wallpaper::find($id);

        if (!$wallpaper) {
            return response()->json(['message' => 'Wallpaper not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'capa' => 'sometimes|url|max:2083',
            'data' => 'sometimes|json'
        ]);

        $wallpaper->update($validatedData);

        return response()->json($wallpaper, 200);
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
}

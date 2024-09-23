<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function getStatus($id)
    {
        $user = auth()->user();
    
        // Decodificar os campos JSON para arrays
        $likes = json_decode($user->likes, true);
        $follows = json_decode($user->follows, true);
        $completes = json_decode($user->completes, true);
        $progress = json_decode($user->progress, true);
    
        // Função para verificar se o ID existe em um array de objetos
        $checkIfIdExists = function($array, $id) {
            if (!is_array($array)) {
                return false;
            }
            foreach ($array as $item) {
                if (isset($item['id']) && $item['id'] == $id) {
                    return true;
                }
            }
            return false;
        };
    
        // Verificar se cada campo contém o ID do mangá
        $likes = $checkIfIdExists($likes, $id);
        $follows = $checkIfIdExists($follows, $id);
        $complete = $checkIfIdExists($completes, $id);
        $progress = $checkIfIdExists($progress, $id);
    
        // Retornar o status baseado nos dados do usuário
        return response()->json([
            'likes' => (bool) $likes,
            'follows' => (bool) $follows,
            'complete' => (bool) $complete,
            'progress' => (bool) $progress,
        ], 200);
    }
}

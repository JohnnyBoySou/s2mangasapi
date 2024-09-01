<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    public function list()
    {
        // Recupera o usuário autenticado
        $user = Auth::user();
 
        // Recupera as coleções do usuário
        $collections = $user->collections;

        return response()->json([
            'status' => true,
            'collections' => $collections,
        ], 200);
    }
}
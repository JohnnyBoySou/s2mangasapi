<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest; 
use App\Models\Comment;

class CommentController extends Controller
{
    public function store(CommentRequest $request)
    {
        try {
            // Obtém o usuário autenticado
            $user = auth()->user();

            // Verifica se o usuário está autenticado
            if (!$user) {
                return response()->json([
                    'error' => 'Faça login para comentar.'
                ], 401);
            }

            // Cria um novo comentário
            $comment = Comment::create([
                'manga_id' => $request->manga_id,
                'user_id' => $user->id,
                'message' => $request->message,
                'parent_id' => $request->parent_id,
            ]);

            // Retorna a resposta JSON com o comentário criado e status 201
            return response()->json([
                'message' => 'Comentário publicado com sucesso.',
                'comment' => $comment,
            ], 201);

        } catch (\Exception $e) {
            // Captura qualquer exceção e retorna uma resposta JSON com status 500 e a mensagem de erro
            return response()->json([
                'error' => 'Ocorreu um erro ao criar o comentário.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index($mangaId)
    {
        $comments = Comment::where('manga_id', $mangaId)->get();
        if ($comments->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum comentário encontrado.'
            ], 404);
        }
        return response()->json($comments, 200);
    }

    public function update(CommentRequest $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->update([
            'message' => $request->message,
        ]);

        return response()->json($comment);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json(null, 204);
    }
}

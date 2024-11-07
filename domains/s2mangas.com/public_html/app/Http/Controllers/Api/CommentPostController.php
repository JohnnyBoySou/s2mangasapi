<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CommentPostController extends Controller
{
    // Função para adicionar um comentário a um post
    public function store(Request $request, $postId)
    {
        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);

        $post = Post::findOrFail($postId);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'content' => $validatedData['content'],
        ]);

        // Atualizar a contagem de comentários do post
        $post->increment('comments_total');

        return response()->json(['message' => 'Comentário adicionado com sucesso!', 'comment' => $comment], 201);
    }

    // Função para editar um comentário
    public function update(Request $request, $commentId)
    {
        $comment = Comment::where('id', $commentId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update(['content' => $validatedData['content']]);

        return response()->json(['message' => 'Comentário atualizado com sucesso!', 'comment' => $comment]);
    }

    // Função para excluir um comentário
    public function destroy($commentId)
    {
        $comment = Comment::where('id', $commentId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Atualizar a contagem de comentários do post
        $comment->post->decrement('comments_total');

        $comment->delete();

        return response()->json(['message' => 'Comentário excluído com sucesso!']);
    }
}

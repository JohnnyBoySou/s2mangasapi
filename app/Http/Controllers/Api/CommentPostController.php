<?php
namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CommentPostController extends Controller
{
    // Adicionar um comentário a um post
    public function store(Request $request, $postId)
    {
        // Validação dos dados recebidos
        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);

        // Obtém o ID do usuário autenticado a partir do token
        $userId = Auth::id();

        // Verifica se o post existe
        $post = Post::findOrFail($postId);

        // Cria o comentário
        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => $userId, // Usa o ID obtido do token
            'content' => $validatedData['content'],
        ]);

        // Atualiza a contagem de comentários do post
        $post->increment('comments_total');

        return response()->json(['message' => 'Comentário adicionado com sucesso!', 'comment' => $comment], 201);
    }
    // Listar comentários de um post
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        $comments = $post->comments_posts() // O método a ser adicionado no modelo Post
            ->with('user:id,name,avatar') // Seleciona apenas id e name do usuário
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                $comment->date = $comment->created_at->diffForHumans();
                return $comment;
            });

        return response()->json($comments);
    }

    // Editar um comentário
    public function update(Request $request, $commentId)
    {
        $comment = PostComment::where('id', $commentId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validatedData = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update(['content' => $validatedData['content']]);

        return response()->json(['message' => 'Comentário atualizado com sucesso!', 'comment' => $comment]);
    }

    // Excluir um comentário
    public function destroy($commentId)
    {
        $comment = PostComment::where('id', $commentId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $comment->delete();

        return response()->json(['message' => 'Comentário excluído com sucesso!']);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentEditRequest;
use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

            // Valida o parent_id, se fornecido
            if ($request->parent_id) {
                $parentComment = Comment::find($request->parent_id);
                if (!$parentComment) {
                    return response()->json([
                        'error' => 'O comentário pai não foi encontrado.'
                    ], 404);
                }
            }
            // Cria um novo comentário
            $comment = Comment::create([
                'manga_id' => $request->manga_id,
                'user_id' => $user->id,
                'message' => $request->message,
                'parent_id' => $request->parent_id,
                'likes' => 0,
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
        $comments = Comment::where('manga_id', $mangaId)
            ->orderBy('likes', 'desc')
            ->get();

        if ($comments->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum comentário encontrado.'
            ], 404);
        }
        $comments = $comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'manga_id' => $comment->manga_id,
                'message' => $comment->message,
                'parent_id' => $comment->parent_id,
                'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
                'likes' => $comment->likes, // Inclua o número de likes, se necessário
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar' => $comment->user->avatar,
                ],
            ];
        });

        return response()->json($comments, 200);
    }

    public function show($mangaId)
    {
        $comments = Comment::where('manga_id', $mangaId)
            ->orderBy('likes', 'desc')
            ->get();

        if ($comments->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum comentário encontrado.'
            ], 404);
        }
        $comments = $comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'manga_id' => $comment->manga_id,
                'message' => $comment->message,
                'parent_id' => $comment->parent_id,
                'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
                'likes' => $comment->likes, // Inclua o número de likes, se necessário
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar' => $comment->user->avatar,
                ],
            ];
        });

        return response()->json($comments, 200);
    }

    public function update(CommentEditRequest $request,$id )
    {
        // Obter o usuário autenticado
        $user = auth()->user(); 
        //$id = $request->id;
        // Verifica se o comentário existe
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['error' => 'Comentário não encontrado.'], 404);
        }
    
        // Verifica se o usuário é o dono do comentário
        if ($user->id !== $comment->user_id) {
            return response()->json(['error' => 'Você não tem permissão para atualizar este comentário.'], 403);
        }
    
        // Atualiza o comentário com a nova mensagem
        $comment->update([
            'message' => $request->message,
        ]);
    
        return response()->json([
            'message' => 'Comentário editado com sucesso.',
            'comment' => $comment,
        ], 200);
    }
    


    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['error' => 'Comentário não encontrado.'], 404);
        }

        if (auth()->id() !== $comment->user_id) {
            return response()->json(['error' => 'Você não tem permissão para deletar este comentário.'], 403);
        }

        $comment->delete();
        return response()->json([
            'message' => 'Comentário excluído com sucesso.',
        ], 200);
    }

    public function like($commentId)
    {
        $userId = auth()->id();

        $comment = Comment::find($commentId);

        if (!$comment) {
            return response()->json([
                'message' => 'Comentário não encontrado.'
            ], 404);
        }

        // Verificar se o usuário já deu like no comentário
        $existingLike = CommentLike::where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if ($existingLike) {
            // Remover like se já existir
            $existingLike->delete();

            // Atualizar a contagem de likes no comentário
            $comment->decrement('likes');

            return response()->json([
                'message' => 'Curtida removida com sucesso.',
                'status' => false,
                'likes' => $comment->likes,
            ], 200);
        } else {
            // Adicionar like se ainda não existir
            CommentLike::create([
                'comment_id' => $commentId,
                'user_id' => $userId,
            ]);

            // Atualizar a contagem de likes no comentário
            $comment->increment('likes');

            return response()->json([
                'message' => 'Comentário curtido com sucesso.',
                'status' => true,
                'likes' => $comment->likes,
            ], 200);
        }
    }

}

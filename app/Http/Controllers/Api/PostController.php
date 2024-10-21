<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    // Criar um novo post
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'image' => 'required|string', // URL ou caminho da imagem
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $post = Post::create([
            'user_id' => Auth::id(),
            'image' => $validatedData['image'],
            'description' => $validatedData['description'] ?? null,
            'color' => $validatedData['color'] ?? null,
        ]);

        return response()->json($post, 201);
    }

    // Adicionar ou remover um like em um post
    public function like($postId)
    {
        $userId = Auth::id();

        // Verifica se o usuário já curtiu o post
        $like = Like::where('post_id', $postId)->where('user_id', $userId)->first();

        if ($like) {
            // Se o like já existe, removê-lo
            $like->delete();
            $status = false;
            $message = 'Like removido';
        } else {
            // Se o like não existe, criar um novo
            Like::create([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
            $status = true;
            $message = 'Like adicionado';
        }

        // Contagem atualizada de likes para o post
        $totalLikes = Like::where('post_id', $postId)->count();

        return response()->json([
            'message' => $message,
            'total_likes' => $totalLikes, // Retorna o total de likes no post
            'liked' => $status, // Retorna se o post foi curtido (true ou false)
        ]);
    }


    public function userPosts()
    {
        $posts = Post::where('user_id', Auth::id())
            ->withCount('likes') // Adiciona a contagem de likes
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($posts);
    }

    // Função para listar todos os posts (do mais recente para o mais antigo)
    public function allPosts()
    {
        $posts = Post::with('user') // Carrega o usuário associado
            ->withCount('likes') // Adiciona a contagem de likes
            ->orderBy('created_at', 'desc')
            ->paginate(10); // Retorna 10 posts por página

        return response()->json($posts);
    }
    // Buscar posts por palavra-chave ou cor
    public function search(Request $request)
    {
        $query = Post::query();

        if ($request->has('keyword')) {
            $query->where('description', 'like', '%' . $request->keyword . '%');
        }

        if ($request->has('color')) {
            $query->where('color', $request->color);
        }

        $posts = $query->orderBy('created_at', 'desc')->get();

        return response()->json($posts);
    }
    public function mostLikedPosts()
    {
        $userId = Auth::id(); // Obtém o ID do usuário autenticado

        $posts = Post::with(['user:id,name,avatar']) // Carrega apenas user_id, name, avatar
            ->withCount('likes') // Adiciona a contagem de likes
            ->orderBy('likes_count', 'desc') // Ordena pelos que têm mais likes
            ->orderBy('created_at', 'desc') // Em caso de empate, ordena por data
            ->paginate(10); // Retorna 10 posts por página

        $posts->getCollection()->transform(function ($post) use ($userId) {
            $post->liked = $post->likes()->where('user_id', $userId)->exists(); // Verifica se o usuário curtiu o post
            return $post;
        });

        return response()->json($posts);
    }
    public function feed()
    {
        $userId = Auth::id(); // Obtém o ID do usuário autenticado

        // Obtém os IDs dos usuários que o usuário autenticado está seguindo
        $followingIds = Auth::user()->following()->pluck('following_id');

        // Consulta os posts dos usuários seguidos, mais recentes
        $posts = Post::with(['user:id,name,avatar']) // Carrega apenas user_id, name, avatar
            ->whereIn('user_id', $followingIds) // Filtra apenas posts dos usuários que o usuário autenticado segue
            ->orderBy('created_at', 'desc') // Ordena pelos mais recentes
            ->paginate(10); // Retorna 10 posts por página

        // Adiciona um campo 'liked' para verificar se o usuário curtiu cada post
        $posts->getCollection()->transform(function ($post) use ($userId) {
            $post->liked = $post->likes()->where('user_id', $userId)->exists(); // Verifica se o usuário curtiu o post
            return $post;
        });

        return response()->json($posts);
    }


    public function update(Request $request, $postId)
    {
        $post = Post::where('id', $postId)->where('user_id', Auth::id())->firstOrFail();

        $validatedData = $request->validate([
            'image' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $post->update($validatedData);

        return response()->json(['message' => 'Post atualizado com sucesso', 'post' => $post]);
    }

    // Excluir um post
    public function destroy($postId)
    {
        $post = Post::where('id', $postId)->where('user_id', Auth::id())->firstOrFail();

        $post->delete();

        return response()->json(['message' => 'Post excluído com sucesso']);
    }

    public function userSinglePosts($id)
    {
        // Obtém os posts do usuário pelo ID fornecido
        $posts = Post::where('user_id', $id)
            ->withCount('likes') // Adiciona a contagem de likes
            ->orderBy('created_at', 'desc') // Ordena pelos posts mais recentes
            ->paginate(10);

        // Verifica se o usuário tem posts
        if ($posts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Nenhum post encontrado para este usuário.',
            ], 404);
        }

        // Retorna os posts do usuário
        return response()->json([
            'status' => true,
            'posts' => $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'image' => $post->image,
                    'description' => $post->description,
                    'color' => $post->color,
                    'likes_count' => $post->likes_count,
                    'created_at' => $post->created_at,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'avatar' => $post->user->avatar,
                    ],
                ];
            }),
        ], 200);
    }

}

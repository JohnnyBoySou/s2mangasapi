<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{

    public function login(Request $request): JsonResponse
    {

        //valida email e senha
        if (
            Auth::attempt([
                "email" => $request->email,
                "password" => $request->password
            ])
        ) {
            //Pegar dados do usuario

            $user = Auth::user();

            $token = $request->user()->createToken('auth_token')->plainTextToken;
            $userData = $user->only(['id', 'name', 'email', 'avatar', 'coins', 'languages', 'genres']);
            $userData['birthdate'] = $user->birthdate ? $user->birthdate->format('d/m/Y') : null;
              
            return response()->json([
                'status' => true,
                'message' => 'Login realizado com sucesso',
                'user' => $userData,
                'token' => $token
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Login ou senha inválidos'
            ], 401);
        }
    }
    public function logout(): JsonResponse
    {
        try {
            $user = Auth::user();
            $user->tokens()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Logout efetuado com sucesso'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'=> false,
                'message' => 'Erro ao efetuar o logout',
            ], 400);
        }
    }
    public function register(UserRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'avatar' => $request->avatar,
                'languages' => $request->languages,
                'genres' =>  $request->genres,
                'birthdate' => $request->birthdate, 
            ]);

            DB::commit();

            if (Auth::attempt(["email" => $request->email, "password" => $request->password])) {
                $token = $user->createToken('auth_token')->plainTextToken;
                $userData = $user->only(['id', 'name', 'email','avatar','coins', 'languages', ]);
                $userData['birthdate'] = $user->birthdate ? $user->birthdate->format('d/m/Y') : null;

                return response()->json([
                    'status' => true,
                    'message' => 'Conta criada com sucesso! redirecionando...',
                    'user' => $userData,
                    'token' => $token,
                ], 201);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Falha ao criar o usuário',
                ], 400);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao criar o usuário',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $userData = $user->only(['id', 'name', 'email', 'avatar', 'coins', 'languages', 'birthdate', 'genres']);
        $userData['birthdate'] = $user->birthdate ? $user->birthdate->format('d/m/Y') : null;

        return response()->json([
            'status' => true,
            'user' => $userData,
        ], 200);
    }

    public function update(UserRequest $request): JsonResponse
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            $user->update($request->only($user->getFillable()));
            $userData = $user->only(['id', 'name', 'email', 'avatar', 'coins', 'languages', 'birthdate', 'genres']);
            $userData['birthdate'] = $user->birthdate ? $user->birthdate->format('d/m/Y') : null;

            DB::commit();
            return response()->json([
                'status' => true,
                'user' => $userData,
                'message' => 'Edição realizada com sucesso.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Falha ao editar o usuário.',
            ], 400);
        }
    }

    public function destroy(): JsonResponse
    {
        $user = Auth::user();
        try {
            $user->delete();
            return response()->json([
                'status' => true,
                'message' => 'Usuário excluído com sucesso',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Usuário não excluído',
            ], 400);
        }
    }

    public function userProfile($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        // Obtém os dados do usuário
        $userData = $user->only(['id', 'name', 'avatar', 'capa', 'bio', 'username', 'languages', 'genres']);

        // Adiciona a contagem de seguidores e seguidos ao array userData
        $userData['followers'] = $user->followers()->count(); // Contagem de seguidores
        $userData['following'] = $user->following()->count(); // Contagem de seguidos

        // Verifica se o usuário autenticado está seguindo o usuário do perfil
        $isFollowing = Auth::user()->following()->where('following_id', $user->id)->exists();
        $userData['isFollowing'] = $isFollowing; // Adiciona o campo isFollowing

        return response()->json([
            'status' => true,
            'user' => $userData,
        ], 200);
    }


    public function genres(): JsonResponse
    {
        $user = Auth::user();
        $userData = $user->only(['genres']);
        $genres = is_string($userData['genres']) ? json_decode($userData['genres'], true) : $userData['genres'];

        return response()->json([
            'status' => true,
            'genres' => $genres
        ], 200);
    }
    public function follow($id): JsonResponse
    {
        $user = Auth::user();
        $userToFollow = User::find($id);

        if (!$userToFollow) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        if ($user->following()->where('following_id', $id)->exists()) {
            return response()->json(['status' => false, 'message' => 'Você já segue este usuário.'], 400);
        }

        $user->following()->attach($id);

        return response()->json(['status' => true, 'message' => 'Você agora segue este usuário.'], 200);
    }

    public function unfollow($id): JsonResponse
    {
        $user = Auth::user();
        $userToUnfollow = User::find($id);

        if (!$userToUnfollow) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        if (!$user->following()->where('following_id', $id)->exists()) {
            return response()->json(['status' => false, 'message' => 'Você não segue este usuário.'], 400);
        }

        $user->following()->detach($id);

        return response()->json(['status' => true, 'message' => 'Você deixou de seguir este usuário.'], 200);
    }

    public function followers($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        // Obtém o usuário autenticado
        $authUser = Auth::user();

        // Seleciona os seguidores do usuário
        $followers = $user->followers()->select('users.id as user_id', 'users.name', 'users.avatar', 'users.bio')->get();

        // Adiciona o campo isFollowing em cada seguidor
        $followers = $followers->map(function ($follower) use ($authUser) {
            $follower->isFollowing = $authUser ? $authUser->isFollowing($follower->user_id) : false;
            return $follower;
        });

        return response()->json(['status' => true, 'followers' => $followers], 200);
    }

    public function following($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        // Obtém o usuário autenticado
        $authUser = Auth::user();

        // Seleciona os usuários que ele está seguindo
        $following = $user->following()->select('users.id as user_id', 'users.name', 'users.avatar', 'users.bio')->get();

        // Adiciona o campo isFollowing em cada seguido
        $following = $following->map(function ($followed) use ($authUser) {
            $followed->isFollowing = $authUser ? $authUser->isFollowing($followed->user_id) : false;
            return $followed;
        });

        return response()->json(['status' => true, 'following' => $following], 200);
    }

    public function isFollowing($id): JsonResponse
    {
        $user = Auth::user();
        $isFollowing = $user->following()->where('following_id', $id)->exists();

        return response()->json(['status' => true, 'is_following' => $isFollowing], 200);
    }

    public function toggleFollowing($id): JsonResponse
    {
        // Obtém o usuário autenticado
        $authUser = Auth::user();

        // Verifica se o usuário autenticado está logado
        if (!$authUser) {
            return response()->json(['status' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        // Verifica se o usuário a ser seguido existe
        $userToFollow = User::find($id);
        if (!$userToFollow) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        // Verifica se já está seguindo
        $isFollowing = $authUser->following()->where('following_id', $userToFollow->id)->exists();

        if ($isFollowing) {
            // Se já está seguindo, para de seguir
            $authUser->following()->detach($userToFollow->id);
            return response()->json(['status' => false, 'message' => 'Parou de seguir.'], 200);
        } else {
            // Se não está seguindo, segue o usuário
            $authUser->following()->attach($userToFollow->id);
            return response()->json(['status' => true, 'message' => 'Começou a seguir.'], 200);
        }
    }

    public function search(Request $request): JsonResponse
    {
        // Valida o parâmetro "name"
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Recupera o nome da requisição
        $name = $request->input('name');

        // Busca usuários cujo nome contenha o texto informado
        $users = User::where('name', 'LIKE', '%' . $name . '%')
            ->select(['id', 'name', 'avatar', 'bio'])
            ->paginate(10);


        // Verifica se encontrou algum usuário
        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'users' => [],
                'message' => 'Nenhum usuário encontrado com esse nome.',
            ], 200);
        }

        // Retorna os resultados
        return response()->json([
            'status' => true,
            'users' => $users,
        ], 200);
    }

}


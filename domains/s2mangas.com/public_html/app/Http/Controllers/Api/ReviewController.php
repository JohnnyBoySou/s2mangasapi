<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // Método para armazenar uma review
    public function store(Request $request)
    {
        // Validação dos dados
        $validatedData = $request->validate([
            'manga_id' => 'required|string',
            'manga_name' => 'required|string|max:255',
            'manga_capa' => 'required|string|max:255',
            'story_rating' => 'required|integer|min:0|max:5',
            'art_rating' => 'required|integer|min:0|max:5',
            'characters_rating' => 'required|integer|min:0|max:5',
            'pacing_rating' => 'required|integer|min:0|max:5',
            'emotion_rating' => 'required|integer|min:0|max:5',
            'description' => 'nullable|string|max:500',
            'name' => 'required|string',
        ], [
            // Mensagens de erro personalizadas
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'min' => 'O campo :attribute deve ter no mínimo :min.',
            'max' => 'O campo :attribute deve ter no máximo :max.',
        ]);

        // Verifica se o usuário já criou uma review para o mesmo mangá
        $existingReview = Review::where('manga_id', $validatedData['manga_id'])
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'Você já criou uma review para este mangá.', 'status' => false], 400);
        }

        // Criação da review
        $review = Review::create([
            'manga_id' => $validatedData['manga_id'],
            'manga_name' => $validatedData['manga_name'],
            'manga_capa' => $validatedData['manga_capa'],
            'user_id' => Auth::id(),
            'story_rating' => $validatedData['story_rating'],
            'art_rating' => $validatedData['art_rating'],
            'characters_rating' => $validatedData['characters_rating'],
            'pacing_rating' => $validatedData['pacing_rating'],
            'emotion_rating' => $validatedData['emotion_rating'],
            'description' => $validatedData['description'],
            'name' => $validatedData['name'],
        ]);

        return response()->json(['message' => 'Review adicionada com sucesso!', 'status' => true, 'review' => $review]);
    }



    // Método para mostrar uma review específica
    public function show($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review não encontrada.', 'status' => false], 404);
        }
        return response()->json(['status' => true, 'review' => $review]);
    }

    // Método para atualizar uma review
    public function edit(Request $request, $id)
    {
        // Tentando encontrar a review pelo ID
        $review = Review::find($id);
        // Verifica se a review foi encontrada
        if (!$review) {
            return response()->json(['message' => 'Review não encontrada.', 'status' => false], 404);
        }

        // Verifica se o usuário é o autor da review
        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Você não tem permissão para editar esta review.', 'status' => false], 403);
        }


        // Validação dos dados
        $validatedData = $request->validate([
            'story_rating' => 'nullable|integer|min:0|max:5',
            'art_rating' => 'nullable|integer|min:0|max:5',
            'characters_rating' => 'nullable|integer|min:0|max:5',
            'pacing_rating' => 'nullable|integer|min:0|max:5',
            'emotion_rating' => 'nullable|integer|min:0|max:5',
            'description' => 'nullable|string|max:500',
            'name' => 'nullable|string',

        ]);

        // Atualiza apenas os campos que foram enviados na requisição
        $review->fill($validatedData);
        $review->save();

        return response()->json(['message' => 'Review atualizada com sucesso!', 'status' => true, 'review' => $review]);

    }


    // Método para deletar uma review
    public function destroy($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review não encontrada.', 'status' => false], 404);
        }

        // Verifica se o usuário é o autor da review
        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Você não tem permissão para deletar esta review.', 'status' => false], 403);
        }

        $review->delete();
        return response()->json(['message' => 'Review deletada com sucesso!', 'status' => true]);
    }

    // Dentro do ReviewController
    public function markHelpful($id)
    {
        // Verifica se a review existe
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review não encontrada.', 'status' => false], 404);
        }

        // Verifica se o feedback já foi dado
        $existingFeedback = ReviewFeedback::where('review_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingFeedback) {
            // Se o feedback já existe, inverta o valor de 'helpful'
            $existingFeedback->helpful = !$existingFeedback->helpful;
            $existingFeedback->save();

            return response()->json(['message' => 'Feedback atualizado com sucesso!', 'status' => true, 'helpful' => $existingFeedback->helpful]);
        } else {
            // Criação do feedback com helpful como true por padrão
            ReviewFeedback::create([
                'review_id' => $id,
                'user_id' => Auth::id(),
                'helpful' => true, // Define como true para o primeiro feedback
            ]);

            return response()->json(['message' => 'Feedback adicionado com sucesso!', 'status' => true, 'helpful' => true]);
        }
    }


    public function statistics($mangaId)
    {
        // Obtém todas as reviews do mangá específico
        $reviews = Review::where('manga_id', $mangaId)->get();

        // Verifica se existem reviews
        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'Nenhuma review encontrada para este mangá.', 'status' => false], 200);
        }

        // Contagem total de reviews
        $totalReviews = $reviews->count();

        // Inicializa um array para contar as médias arredondadas
        $ratingDistribution = array_fill(0, 6, 0); // Para médias arredondadas de 0 a 5

        // Variável para armazenar o total das notas (soma das médias)
        $totalRateSum = 0;

        // Calcula a média de cada review e popula a distribuição
        foreach ($reviews as $review) {
            // Calcula a média de cada review
            $averageRating = ($review->story_rating + $review->art_rating + $review->characters_rating + $review->pacing_rating + $review->emotion_rating) / 5;

            // Soma a média no total rate
            $totalRateSum += $averageRating;

            // Arredonda a média para o valor mais próximo entre 0 e 5
            $roundedAverage = round($averageRating);
            $ratingDistribution[$roundedAverage]++;
        }

        // Calcula a porcentagem de reviews para cada média arredondada
        $ratingPercentages = [];
        for ($i = 0; $i <= 5; $i++) {
            $ratingPercentages[$i] = ($ratingDistribution[$i] / $totalReviews) * 100;
        }

        // Calcula a média total (total rate) dividindo o total das notas pelo número de reviews
        $totalRate = $totalRateSum / $totalReviews;

        return response()->json([
            'status' => true,
            'manga_id' => $mangaId,
            'total_reviews' => $totalReviews,
            'total_rate' => round($totalRate, 2), // Total rate arredondado para 2 casas decimais
            'rating_distribution' => [
                '0' => $ratingPercentages[0] . '%',
                '1' => $ratingPercentages[1] . '%',
                '2' => $ratingPercentages[2] . '%',
                '3' => $ratingPercentages[3] . '%',
                '4' => $ratingPercentages[4] . '%',
                '5' => $ratingPercentages[5] . '%',
            ],
        ]);
    }




    public function single($mangaId)
    {

        $query = Review::where('manga_id', $mangaId); // Filtra diretamente pelo manga_id na URL

        // Ordenação automática por data de criação (mais recente primeiro)
        $query->orderBy('created_at', 'desc');

        // Busca as reviews
        $reviews = $query->get();

        // Verifica se não há reviews
        if ($reviews->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Nenhuma review encontrada para o manga especificado.'], 404);
        }

        // Retorna a resposta com status e as reviews
        return response()->json(['status' => true, 'reviews' => $reviews]);
    }

    public function userReviews(Request $request)
    {
        // Obtém o ID do usuário autenticado
        $userId = Auth::id();

        // Busca todas as reviews do usuário autenticado
        $reviews = Review::where('user_id', $userId)->get();

        // Retorna a resposta com status e as reviews
        return response()->json(['status' => true, 'reviews' => $reviews]);
    }

    public function userReviewsById(Request $request, $id)
    {
        // Verifica se o usuário existe (opcional, dependendo de como você trata usuários)
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.', 'status' => false], 404);
        }

        // Busca todas as reviews do usuário conforme o ID enviado
        $reviews = Review::where('user_id', $id)->get();

        // Verifica se o usuário possui reviews
        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'Nenhuma review encontrada para este usuário.', 'status' => false], 404);
        }

        // Retorna a resposta com status e as reviews
        return response()->json(['status' => true, 'reviews' => $reviews]);
    }

}

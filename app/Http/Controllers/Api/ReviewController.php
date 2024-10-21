<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewFeedback;
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
            'story_rating' => 'required|integer|min:0|max:5',
            'art_rating' => 'required|integer|min:0|max:5',
            'characters_rating' => 'required|integer|min:0|max:5',
            'pacing_rating' => 'required|integer|min:0|max:5',
            'emotion_rating' => 'required|integer|min:0|max:5',
            'description' => 'nullable|string|max:500',
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
            'user_id' => Auth::id(),
            'story_rating' => $validatedData['story_rating'],
            'art_rating' => $validatedData['art_rating'],
            'characters_rating' => $validatedData['characters_rating'],
            'pacing_rating' => $validatedData['pacing_rating'],
            'emotion_rating' => $validatedData['emotion_rating'],
            'description' => $validatedData['description'],
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
    public function update(Request $request, $id)
    {
        // Tentando encontrar a review pelo ID
        $review = Review::find($id);

        // Para depuração, exibe a review encontrada (ou null se não for encontrada)

        if (!$review) {
            return response()->json(['message' => 'Review não encontrada.', 'status' => false], 404);
        }

        // Verifica se o usuário é o autor da review
        if ($review->user_id !== Auth::id()) {
            dd($review->user_id);

            return response()->json(['message' => 'Você não tem permissão para editar esta review.', 'status' => false], 403);
        }
        /*

// Validação dos dados
$validatedData = $request->validate([
    'manga_id' => 'required|string',
    'story_rating' => 'required|integer|min:0|max:5',
    'art_rating' => 'required|integer|min:0|max:5',
    'characters_rating' => 'required|integer|min:0|max:5',
    'pacing_rating' => 'required|integer|min:0|max:5',
    'emotion_rating' => 'required|integer|min:0|max:5',
    'description' => 'nullable|string|max:500',
]);

// Atualização da review
$review->update($validatedData);

return response()->json(['message' => 'Review atualizada com sucesso!', 'status' => true, 'review' => $review]);
*/
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
    public function markHelpful(Request $request, $id)
    {
        // Valida que o usuário está autenticado
        $request->validate([
            'helpful' => 'required|boolean',
        ]);

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
            return response()->json(['message' => 'Você já deu feedback para esta review.', 'status' => false], 400);
        }

        // Criação do feedback
        ReviewFeedback::create([
            'review_id' => $id,
            'user_id' => Auth::id(),
            'helpful' => $request->input('helpful'),
        ]);

        return response()->json(['message' => 'Feedback adicionado com sucesso!', 'status' => true]);
    }

    public function statistics($mangaId)
    {
        // Obtém todas as reviews do mangá específico
        $reviews = Review::where('manga_id', $mangaId)->get();

        // Verifica se existem reviews
        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'Nenhuma review encontrada para este mangá.', 'status' => false], 404);
        }

        // Calcula a média de notas
        $averageStoryRating = $reviews->avg('story_rating');
        $averageArtRating = $reviews->avg('art_rating');
        $averageCharactersRating = $reviews->avg('characters_rating');
        $averagePacingRating = $reviews->avg('pacing_rating');
        $averageEmotionRating = $reviews->avg('emotion_rating');

        // Contagem total de reviews
        $totalReviews = $reviews->count();

        // Distribuição de notas
        $ratingDistribution = [
            'story' => $this->ratingDistribution($reviews, 'story_rating'),
            'art' => $this->ratingDistribution($reviews, 'art_rating'),
            'characters' => $this->ratingDistribution($reviews, 'characters_rating'),
            'pacing' => $this->ratingDistribution($reviews, 'pacing_rating'),
            'emotion' => $this->ratingDistribution($reviews, 'emotion_rating'),
        ];

        return response()->json([
            'status' => true,
            'manga_id' => $mangaId,
            'total_reviews' => $totalReviews,
            'average_ratings' => [
                'story' => $averageStoryRating,
                'art' => $averageArtRating,
                'characters' => $averageCharactersRating,
                'pacing' => $averagePacingRating,
                'emotion' => $averageEmotionRating,
            ],
            'rating_distribution' => $ratingDistribution,
        ]);
    }

    // Método auxiliar para calcular a distribuição de notas
    private function ratingDistribution($reviews, $ratingField)
    {
        $distribution = array_fill(0, 6, 0); // Para notas de 0 a 5

        foreach ($reviews as $review) {
            $rating = $review->$ratingField;
            $distribution[$rating]++;
        }

        return $distribution;
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

}

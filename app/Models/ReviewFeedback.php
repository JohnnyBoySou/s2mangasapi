<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewFeedback extends Model
{
    use HasFactory;

    protected $table = 'review_feedbacks';

    protected $fillable = [
        'review_id',
        'user_id',
        'helpful',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    // Adicione este método se quiser verificar se o feedback já foi dado pelo usuário
    public static function feedbackGiven($reviewId, $userId)
    {
        return self::where('review_id', $reviewId)
            ->where('user_id', $userId)
            ->first();
    }
}

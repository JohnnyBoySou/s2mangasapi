<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // Defina o nome da tabela
    protected $table = 'reviews';

    // Defina os campos que podem ser preenchidos em massa (mass assignment)
    protected $fillable = [
        'manga_name', 
        'manga_capa',
        'manga_id',
        'user_id',
        'story_rating',
        'art_rating',
        'characters_rating',
        'pacing_rating',
        'emotion_rating',
        'description',
        'name',
    ];

    // Relacionamento com o usuário (assumindo que você tem uma tabela de usuários)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

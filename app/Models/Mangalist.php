<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mangalist extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'capa',
        'color',
        'descricao',
        'mangas_id',
        'type',
        'user_id',
    ];


    public function likes()
    {
        return $this->hasMany(MangalistLike::class, 'mangalist_id');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}

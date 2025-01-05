<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'mangas_id',
        'status',
        'capa',
        'genres',
        'fixed',
        'user_id',
    ];

    protected $casts = [
        'mangas_id' => 'array',
    ];
    protected $attributes = [
        'mangas_id' => '[]', // Valor padrão como array vazio
    ];    public function likes()
    {
        return $this->hasMany(CollectionLike::class, 'collection_id');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}

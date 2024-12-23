<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
    protected $appends = ['date']; // Adiciona 'date' ao JSON retornado

    public function getMangasIdAttribute($value)
    {
        return json_decode($value, true); // Decodifica o JSON como array associativo
    }
    public function likes()
    {
        return $this->hasMany(MangalistLike::class, 'mangalist_id');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }
}

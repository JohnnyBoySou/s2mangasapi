<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MangaItem extends Model
{
    use HasFactory;

    protected $table = 'mangas';  // A tabela 'mangas' será usada para o modelo MangaItem
    protected $fillable = [
        'uuid',  // Adicionando o campo uuid
        'name', 
        'description', 
        'capa', 
        'release_date',
        'status',
        'type',
        'year',
        'languages', 
        'categories', 
        'create_date',
        'user_id',
    ];

    public function likes()
    {
        return $this->hasMany(MangaLike::class, 'manga_id');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
    protected $casts = [
        'description' => 'array',
        'languages' => 'array', 
        'categories' => 'array',   
    ];
}

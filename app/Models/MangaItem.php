<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MangaItem extends Model
{
    use HasFactory;
    protected $table = 'mangas';  
    protected $fillable = [
        'uuid',  
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

    public function getDescriptionByLocale($locale)
    {
        // Verificar se o idioma existe e, se não, retornar o idioma padrão (en)
        if (isset($this->description[$locale])) {
            return $this->description[$locale];
        }

        // Caso não encontre, tenta retornar o valor para 'en' ou retorna um texto padrão
        return $this->description['en'] ?? 'Description not available';
    }
}

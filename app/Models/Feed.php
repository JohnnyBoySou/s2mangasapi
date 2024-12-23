<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    use HasFactory;

    // Defina a tabela se não seguir a convenção plural do Laravel
    protected $table = 'feeds';

    // Defina os campos que podem ser preenchidos
    protected $fillable = [
        'mangalist_id',
        'title',
        'manga_ids', // Armazenando IDs de mangás em formato JSON
    ];

    // Se manga_ids for armazenado como JSON, adicione isso no cast
    protected $casts = [
        'manga_ids' => 'array',
    ];

    // Relacionamento com MangaItem (caso queira acessar diretamente os mangas do feed)
    public function mangas()
    {
        return $this->belongsToMany(MangaItem::class, 'manga_feed', 'feed_id', 'manga_id');
    }
}

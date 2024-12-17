<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MangaLike extends Model
{
    use HasFactory;

    protected $table = 'mangas_likes'; // Nome da tabela

    protected $fillable = [
        'manga_id',
        'user_id',
    ];

    public function manga()
    {
        return $this->belongsTo(MangaItem::class, 'manga_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

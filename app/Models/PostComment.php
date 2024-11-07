<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostComment extends Model
{
    use HasFactory;

    protected $table = 'comments_posts'; // Adicione esta linha para especificar o nome da tabela
    protected $fillable = ['post_id', 'user_id', 'content'];

    // Relacionamento com o post
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Relacionamento com o usuário
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

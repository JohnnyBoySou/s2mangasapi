<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Comment extends Model
{
    use HasFactory;
    
    protected $fillable = ['manga_id', 'user_id', 'parent_id', 'message'];

    // Relacionamento com o usuário
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento com o mangá
    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    // Relacionamento com respostas (comentários filhos)
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Relacionamento com o comentário pai (se for uma resposta)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
}

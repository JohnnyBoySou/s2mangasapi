<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'image', 'description', 'color', 'manga_id', 'manga_name', 'manga_capa'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // Relacionamento com comentários
    public function comments_posts(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }
}

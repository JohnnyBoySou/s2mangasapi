<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MangalistLike extends Model
{
    use HasFactory;

    protected $table = 'mangalists_likes'; // Nome da tabela

    protected $fillable = [
        'mangalist_id',
        'user_id',
    ];

    public function mangalist()
    {
        return $this->belongsTo(Mangalist::class, 'mangalist_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

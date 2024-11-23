<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionLike extends Model
{
    use HasFactory;

    protected $table = 'collections_likes'; // Nome da tabela

    protected $fillable = [
        'collection_id',
        'user_id',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

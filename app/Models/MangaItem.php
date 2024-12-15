<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MangaItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'capa',
        'categories',
        'languages',
        'release_date',
        'status',
        'type',
        'year',
    ];

    protected $casts = [
        'categories' => 'array',
        'languages' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'mangas_id',
        'status',
        'capa',
        'genres',
        'fixed',
        'user_id',
    ];
    
}

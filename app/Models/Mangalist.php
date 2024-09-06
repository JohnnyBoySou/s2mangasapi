<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mangalist extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'capa',
        'color',
        'descricao',
        'mangas_id',
        'type',
        'user_id',
    ];

    protected $hidden = [
        //'user_id',
    ];

}

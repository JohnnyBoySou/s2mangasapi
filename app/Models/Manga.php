<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manga extends Model
{
    protected $fillable = [
        'id',
        'name',
        'capa',
        'user_id'
    ];

    
   
}

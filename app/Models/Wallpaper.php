<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallpaper extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'capa', 'data'];

    protected $casts = [
        'data' => 'array',
    ];
}

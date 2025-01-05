<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Wallpaper extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'capa', 'data', 'user_id'];

    protected $casts = [
        'data' => 'array',
    ];
    
    protected $appends = ['date']; 

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'bio',
        'capa',
        'avatar',
        'collections',
        'progress',
        'complete',
        'likes',
        'follows',
        'marks',
        'history',
        'diamonds',
        'coins',
        'languages',
        'preferences',
        'genres',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'languages' => 'array',  // Se languages for um JSON, você pode fazer o casting para array
        'genres' => 'array',     // Faz o mesmo para genres, caso seja JSON
    ];

    /**
     * Usuários que o usuário está seguindo.
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id');
    }

    /**
     * Usuários que estão seguindo o usuário.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id');
    }

    public function isFollowing($userId)
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

}

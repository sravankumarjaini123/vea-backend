<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Twitters extends Model
{
    use HasFactory;
    protected $table = 'twitters';

    /*
    * Get the Details of the users for connected twitter
    */
    public function twitter()
    {
        return $this->belongsToMany(User::class, 'users_twitters', 'twitters_id', 'users_id')
            ->withPivot(['id', 'twitter_user_id', 'username', 'access_token', 'token_type', 'profile_picture_url', 'refresh_token', 'auth_type', 'auth_id', 'shareable_password']);
    }

    /*
    * Get the Details of the Posts for connected twitter
    */
    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'twitters_posts')
            ->withPivot(['users_twitters_id', 'users_id', 'twitter_post_id', 'text', 'tweeted_by', 'retweeted', 'retweeted_by', 'updated_at', 'disconnected'])
            ->withTimestamps();
    }
}

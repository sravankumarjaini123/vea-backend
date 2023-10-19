<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkedIns extends Model
{
    use HasFactory;
    protected $table = 'linkedins';

    /*
    * Get the Details of the users for connected LinkedIn
    */
    public function user()
    {
        return $this->belongsToMany(User::class, 'users_linkedins', 'linkedins_id', 'users_id')
            ->withPivot(['linkedin_user_id','organisation_id', 'username', 'profile_picture_url', 'access_token', 'refresh_token', 'token_type']);
    }

    /*
    * Get the Details of the Posts for connected LinkedIn
    */
    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'linkedins_posts', 'linkedins_id', 'posts_id')
            ->withPivot(['linkedin_post_id','content_type', 'subject', 'body', 'visibility','media_id', 'external_url', 'shared_by', 'reshared', 'reshared_by', 'media_id', 'disconnected', 'share_type']);
    }
}

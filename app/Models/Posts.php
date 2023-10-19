<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Posts extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'posts';

    protected $casts = [
        'media' => 'array',
        'galleries' => 'array',
        'related_posts' => 'array'
    ];

    /**
     * Get the Main File of the Post
     */
    public function postFile()
    {
        return $this->belongsTo(FoldersFiles::class);
    }

    /**
     * Get the Thumbnail of the Post
     */
    public function postThumbnail()
    {
        return $this->belongsTo(FoldersFiles::class);
    }

    /**
     * Get the Status of the Post
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the Groups of the Post.
     */
    public function groups()
    {
        return $this->belongsToMany(Groups::class, 'posts_groups');
    }

    /**
     * Get the Categories of the Post.
     */
    public function categories()
    {
        return $this->belongsToMany(Categories::class, 'posts_categories');
    }

    /**
     * Get the Categories of the Post.
     */
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'posts_tags', 'posts_id', 'tags_id');
    }

    /**
     * Get the Authors of the Post.
     */
    public function authors()
    {
        return $this->belongsToMany(Authors::class, 'posts_authors', 'posts_id', 'authors_id');
    }

    /**
     * Get the Details of the Wordpress for the Post
     */
    public function wordpress()
    {
        return $this->belongsToMany(Wordpress::class, 'wordpress_posts')->withPivot(['wp_post_id','sync_status', 'updated_at']);
    }

    /**
     * Get the Details of the Twitter for the Post
     */
    public function twitter()
    {
        return $this->belongsToMany(Twitters::class, 'twitters_posts')
            ->withPivot(['users_twitters_id', 'users_id', 'twitter_post_id', 'text', 'tweeted_by', 'retweeted', 'retweeted_by', 'updated_at', 'disconnected'])
            ->withTimestamps('created_at', 'updated_at')->orderBy('created_at', 'desc');
    }

    public function postsStatistics()
    {
        return $this->hasMany(PostsStatistics::class, 'posts_id');
    }

    /**
     * Get the Details of the LinkedIn for the Post
     */
    public function linkedIn()
    {
        return $this->belongsToMany(LinkedIns::class, 'linkedins_posts', 'posts_id', 'linkedins_id')
            ->withPivot(['linkedin_post_id','content_type', 'subject', 'body', 'visibility','media_id', 'external_url', 'shared_by', 'reshared', 'reshared_by', 'media_id', 'disconnected', 'share_type']);
    }
}

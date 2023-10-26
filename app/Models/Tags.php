<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    use HasFactory;
    protected $table = 'tags';

    /**
     * Get the Posts for this Tag
     */
    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'posts_tags');
    }

    /**
     * Get the Wordpress for this Tag
     */
    public function wordpress()
    {
        return $this->belongsToMany(Wordpress::class, 'wordpress_tags')->withPivot('wp_tag_id');
    }
}

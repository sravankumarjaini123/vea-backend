<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wordpress extends Model
{
    use HasFactory;
    protected $table = 'wordpress';

    /**
     * Get the Categories of the Wordpress.
     */
    public function categories()
    {
        return $this->belongsToMany(Categories::class, 'wordpress_categories')->withPivot('wp_category_id');
    }
    /**
     * Get the Posts of the Wordpress.
     */
    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'wordpress_posts')->withPivot(['wp_post_id', 'sync_status', 'updated_at']);
    }
    /**
     * Get the tags of the Wordpress.
     */
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'wordpress_tags')->withPivot('wp_tag_id');
    }
    /**
     * Get the files of the Wordpress.
     */
    public function files()
    {
        return $this->belongsToMany(FoldersFiles::class, 'wordpress_files', 'wordpress_id', 'files_id')->withPivot('wp_file_id');
    }
}

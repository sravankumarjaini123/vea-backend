<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    protected $table = 'categories';

    /**
     * Get the Posts for the Category
     */
    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'posts_categories');
    }

    /**
     * Get the wordpress for this category
     */
    public function wordpress()
    {
        return $this->belongsToMany(Wordpress::class, 'wordpress_categories')->withPivot('wp_category_id');
    }
}

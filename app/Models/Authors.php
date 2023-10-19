<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authors extends Model
{
    use HasFactory;
    protected $table = 'authors';

    /**
     * Get the Salutation of the User.
     */
    public function salutation()
    {
        return $this->belongsTo(Salutations::class, 'salutations_id');
    }

    /**
     * Get the Title of the User.
     */
    public function title()
    {
        return $this->belongsTo(Titles::class, 'titles_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'posts_authors', 'authors_id', 'posts_id');
    }
}

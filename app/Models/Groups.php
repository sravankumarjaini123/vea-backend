<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{
    use HasFactory;
    protected $table = 'groups';

    /**
     * Get the Posts for the Group
     */
    public function posts()
    {
        return $this->belongsToMany(Posts::class, 'posts_groups');
    }
}

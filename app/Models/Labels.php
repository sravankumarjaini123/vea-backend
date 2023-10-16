<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Labels extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_order'];

    /**
     * Get the Partners of the Label.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_labels', 'labels_id', 'users_id');
    }

    /**
     * Get the Partners of the Label.
     */
    public function partners()
    {
        return $this->belongsToMany(Partners::class, 'partners_labels');
    }
}

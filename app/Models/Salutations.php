<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salutations extends Model
{
    use HasFactory;

    /**
     * Get the Users for the Salutation.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'salutations_id');
    }
}

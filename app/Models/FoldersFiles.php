<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FoldersFiles extends Model
{
    use HasFactory;
    protected $table = 'folders_files';

    /**
     * Get the User details of the Profile Picture file
     */
    public function user() :HasOne
    {
        return $this->hasOne(User::class,'profile_photo_id');
    }
}

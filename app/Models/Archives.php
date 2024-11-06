<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archives extends Model
{
    use HasFactory;
    protected $table = 'archives';

    /**
     * Get the File of the Archive
     */
    public function file()
    {
        return $this->belongsTo(FoldersFiles::class, 'file_id');
    }
}

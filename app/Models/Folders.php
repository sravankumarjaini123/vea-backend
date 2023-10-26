<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folders extends Model
{
    use HasFactory;
    protected $table = 'folders';
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    /**
     * trait expects name is parent_id but we override it with parents_id.
     */
    public function getParentKeyName()
    {
        return 'parents_id';
    }

    /**
     * trait uses the models primary key as local key, here we can override.
     */
    public function getLocalKeyName()
    {
        return 'id';
    }
}

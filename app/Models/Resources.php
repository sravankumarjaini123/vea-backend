<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resources extends Model
{
    use HasFactory;

    public function users() {
        return $this->belongsToMany(User::class,'users_resources_permissions');
    }

    public function roles() {
        return $this->belongsToMany(Roles::class, 'roles_resources', 'roles_id', 'resources_id')
            ->withPivot('permissions_id');
    }
}

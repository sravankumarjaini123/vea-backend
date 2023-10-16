<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    use HasFactory;

    public function permissions() {
        return $this->belongsToMany(Permissions::class,'roles_permissions');
    }

    public function users() {
        return $this->belongsToMany(User::class,'users_roles', 'roles_id', 'users_id');
    }

    public function resources() {
        return $this->belongsToMany(Resources::class,'roles_resources', 'roles_id', 'resources_id')
            ->withPivot('permissions_id');
    }
}

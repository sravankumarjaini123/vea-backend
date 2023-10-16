<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permissions extends Model
{
    use HasFactory;

    public function roles() {
        return $this->belongsToMany(Roles::class,'roles_permissions');
    }

    public function users() {
        return $this->belongsToMany(User::class,'users_permissions');
    }
}

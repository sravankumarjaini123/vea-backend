<?php

namespace App\Permissions;

use App\Models\Permissions;
use App\Models\Resources;
use App\Models\Roles;

trait HasPermissionsTrait
{
    public function givePermissionsTo(... $permissions) {
        $permissions = $this->getAllPermissions($permissions);
        if($permissions === null) {
            return $this;
        }
        $this->permissions()->saveMany($permissions);
        return $this;
    }

    public function withdrawPermissionsTo( ... $permissions ) {
        $permissions = $this->getAllPermissions($permissions);
        $this->permissions()->detach($permissions);
        return $this;
    }

    public function refreshPermissions( ... $permissions ) {
        $this->permissions()->detach();
        return $this->givePermissionsTo($permissions);
    }

    public function hasPermissionTo(...$permission) {
        foreach ($permission as $per){
            $this->hasPermission($per);
        }
    }

    public function hasPermissionThroughRole($permission) {
        foreach ($permission->roles as $role){
            if($this->roles->contains($role)) {
                return true;
            }
        }
        return false;
    }

    public function getUserRole()
    {
        return $this->roles;
    }

    /**
     * Check whether Role has the same permissions to proceed further for user.
     */
    public function hasResourceRole( $resource , $role_id , $permission_id)
    {
        $role = Roles::where('id', $role_id)->first();
        $resources = Resources::where('slug', $resource)->first();
        $role_resources = $role->resources()->where('resources_id',$resources->id)
            ->where('permissions_id', $permission_id)->exists();
        if ($role_resources){
            return true;
        } else {
            return false;
        }
    } // End Function

    /**
     * Check whether User has permission for the respective action to be performed
     */
    public function hasUserPermission($resource, $permission){
        $resource = Resources::where('slug', $resource)->first();
        $permission = Permissions::where('slug', $permission)->first();
        $user_resource_permissions = $this->resources()->where('resources_id', $resource->id)->get();
        foreach ($user_resource_permissions as $user_resource_permission){
            if ($permission->id === $user_resource_permission->pivot->permissions_id){
                return true;
            }
        }
        return false;
    }

    public function roles() {
        return $this->belongsToMany(Roles::class,'users_roles', 'users_id', 'roles_id');
    }

    public function permissions() {
        return $this->belongsToMany(Permissions::class,'users_permissions', 'users_id', 'permissions_id');
    }

    public function resources() {
        return $this->belongsToMany(Resources::class,'users_resources_permissions', 'users_id', 'resources_id')
            ->withPivot('permissions_id');
    }

    protected function hasPermission($permission) {
        return (bool) $this->permissions->where('slug', $permission)->count();
    }

    protected function getAllPermissions(array $permissions) {
        return Permissions::whereIn('slug',$permissions)->get();
    }
} // End Function

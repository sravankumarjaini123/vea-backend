<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersLogin extends Model
{
    use HasFactory;

    protected $table = 'users_login';
    protected $fillable = ['users_id', 'ip', 'date', 'browser_agent', 'status'];
}

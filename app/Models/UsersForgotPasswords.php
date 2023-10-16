<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersForgotPasswords extends Model
{
    use HasFactory;
    protected $table = 'users_forgot_passwords';
}

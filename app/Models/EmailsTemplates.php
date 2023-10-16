<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailsTemplates extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'previous_state', 'type'];
}

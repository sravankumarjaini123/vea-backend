<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingsStates extends Model
{
    use HasFactory;
    protected $table = 'fundings_states';

    public function fundings()
    {
        return $this->belongsToMany(Fundings::class, 'fundings_fundings_states');
    }
}

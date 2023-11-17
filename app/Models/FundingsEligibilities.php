<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingsEligibilities extends Model
{
    use HasFactory;
    protected $table = 'fundings_eligibilities';

    public function fundings()
    {
        return $this->belongsToMany(Fundings::class, 'fundings_fundings_eligibilities');
    }
}

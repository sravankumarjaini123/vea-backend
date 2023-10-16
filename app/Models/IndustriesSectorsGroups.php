<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustriesSectorsGroups extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function sectorsGroups(): HasMany
    {
        return $this->hasMany(IndustriesSectors::class, 'industries_sectors_groups_id');
    }
}

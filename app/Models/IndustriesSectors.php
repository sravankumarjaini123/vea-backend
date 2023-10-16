<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class IndustriesSectors extends Model
{
    use HasFactory;

    /**
     * Get the Partners of the IndustrySectors.
     */
    public function industriesSectorsGroups(): BelongsToMany
    {
        return $this->belongsToMany(Partners::class, 'partners_industries_sectors');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Measures extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'measures';

    /**
     * Get the Processor of the Measure
     */
    public function processor()
    {
        return $this->belongsTo(MeasuresProcessors::class, 'measures_processors_id');
    }

    /**
     * Get the Category of the Measure
     */
    public function category()
    {
        return $this->belongsTo(MeasuresCategories::class, 'measures_categories_id');
    }

    /**
     * Get the Type of the Measure
     */
    public function type()
    {
        return $this->belongsTo(MeasuresTypes::class, 'measures_types_id');
    }

    /**
     * Get the Industry Sector of the Measure
     */
    public function industrySector()
    {
        return $this->belongsTo(IndustriesSectors::class, 'industries_sectors_id');
    }

    /**
     * Get the Contact Person of the Measure
     */
    public function contact()
    {
        return $this->belongsTo(User::class, 'contacts_persons_id');
    }

    /**
     * Get the Energyy Sources the Measure
     */
    public function energySources()
    {
        return $this->belongsToMany(MeasuresEnergySources::class, 'measures_measures_energy_sources', 'measures_id', 'measures_energy_sources_id')
            ->withPivot('id', 'measures_energy_savings');
    }
}


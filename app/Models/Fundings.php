<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fundings extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'fundings';

    /**
     * Get the States of the Fundings
     */
    public function states()
    {
        return $this->belongsToMany(FundingsStates::class, 'fundings_fundings_states');
    }

    /**
     * Get the Subjects of the Fundings
     */
    public function subjects()
    {
        return $this->belongsToMany(FundingsSubjects::class, 'fundings_fundings_subjects');
    }

    /**
     * Get the Eligibilities of the Fundings
     */
    public function eligibilities()
    {
        return $this->belongsToMany(FundingsEligibilities::class, 'fundings_fundings_eligibilities');
    }

    /**
     * Get the Status of the Post
     */
    public function requirement()
    {
        return $this->belongsTo(FundingsRequirements::class, 'fundings_requirements_id');
    }

    /**
     * Get the Status of the Post
     */
    public function type()
    {
        return $this->belongsTo(FundingsTypes::class, 'fundings_types_id');
    }

    /**
     * Get the Status of the Post
     */
    public function body()
    {
        return $this->belongsTo(FundingsBodies::class, 'fundings_bodies_id');
    }

    /**
     * Get the Contact Person of the Measure
     */
    public function contact()
    {
        return $this->belongsTo(User::class, 'contacts_persons_id');
    }
}

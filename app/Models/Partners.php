<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partners extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'code',
        'name',
        'street',
        'street_extra',
        'zip_code',
        'city',
        'country_id',
    ];

    /**
     * Method allow to get country details of particular partner.
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Countries::class, 'countries_id');
    }

    /**
     * Method allow to get collection of labels of particular partner.
     * @return BelongsToMany
     */
    public function partnersLabels(): BelongsToMany
    {
        return $this->belongsToMany(Labels::class, 'partners_labels');
    }

    /**
     * Method allow to get collection of Industry sectors of particular partners
     * @return BelongsToMany
     */
    public function partnersIndustriesSectors(): BelongsToMany
    {
        return $this->belongsToMany(IndustriesSectors::class, 'partners_industries_sectors');
    }

    /**
     * Method allow to get collection of Users of particular partner
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'partners_id');
    }

    /**
     * Method allow to get Rectangle logo of the Partner
     */
    public function partnerRectangleLogo()
    {
        return $this->belongsTo(FoldersFiles::class,'logo_rectangle_file_id');
    }

    /**
     * Method allow to get Square logo of the Partner
     */
    public function partnerSquareLogo()
    {
        return $this->belongsTo(FoldersFiles::class,'logo_square_file_id');
    }

} // End class

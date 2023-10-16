<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PagesSectionsNewsLetters;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewslettersInterests extends Model
{
    use HasFactory;

    protected $table = 'newsletters_interests';
    protected $fillable = ['name', 'display_order'];

    /**
     * The PagesSectionsNewsLetters that belong to the NewslettersInterests.
     */
    public function pageSectionNewsletters(): BelongsToMany
    {
        return $this->belongsToMany(PagesSectionsNewsLetters::class, 'pages_sections_newsletters_interests');
    }

    /**
     * The newslettersUsersIntersts that belong to the NewslettersInterests.
     */
    public function newslettersUsersIntersts(): BelongsToMany
    {
        return $this->belongsToMany(NewslettersUsers::class, 'newsletters_users_interests', 'newsletters_interests_id', 'newsletters_users_id');
    }

    /**
     * The newslettersUsersIntersts that belong to the NewslettersInterests.
     */
    public function newslettersUsers(): HasMany
    {
        return $this->hasMany(NewslettersUsers::class, 'newsletters_users_interests');
    }
}

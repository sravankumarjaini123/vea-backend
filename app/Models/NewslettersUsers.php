<?php

namespace App\Models;

use App\Models\NewslettersUsersInterests;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Salutations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewslettersUsers extends Model
{
    use HasFactory;

    protected $table = 'newsletters_users';
    protected $fillable = ['salutations_id', 'hashed_user_email', 'titles_id', 'firstname', 'lastname', 'email', 'contacts_id', 'is_activated', 'activation_date', 'ip_address', 'browser_agent'];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacts_id');
    }

    public function salutations(): BelongsTo
    {
        return $this->belongsTo(Salutations::class);
    }

    /**
     * The newslettersUsersIntersts that belong to the NewslettersUsers.
     */
    public function newslettersInterests(): BelongsToMany
    {
        return $this->belongsToMany(NewslettersInterests::class, 'newsletters_users_interests');;
    }

    /**
     * The newslettersUsersIntersts that belong to the NewslettersInterests.
     */
    public function newslettersUsersIntersts(): BelongsToMany
    {
        return $this->belongsToMany(NewslettersUsers::class, 'newsletters_users_interests', 'newsletters_users_id', 'newsletters_interests_id');
    }
}

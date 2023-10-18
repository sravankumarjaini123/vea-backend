<?php

namespace App\Models;

use App\Permissions\HasPermissionsTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;use Laravel\Passport\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasPermissionsTrait;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * Get the Salutation of the User.
     */
    public function salutation(): BelongsTo
    {
        return $this->belongsTo(Salutations::class, 'salutations_id');
    }

    /**
     * Get the Title of the User.
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Titles::class, 'titles_id');
    }

    /**
     * Get the Login Details of the User
     */
    public function userLogins(): HasMany
    {
        return $this->hasMany(UsersLogin::class, 'users_id')->orderBy('id', 'desc');
    }

    /**
     * Get the labels of the Contact.
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Labels::class, 'users_labels', 'users_id', 'labels_id');
    }

    /**
     * Get the user that owns the country.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Countries::class, 'country_id');
    }

    /**
     * Get the user that owns the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Partners::class, 'partners_id');
    }

    /**
     * Get the Profile Photo of the User.
     */
    public function profilePhoto()
    {
        return $this->belongsTo(FoldersFiles::class);
    }

    /**
     * Get the Notifications Triggered by the User
     */
    public function notifications()
    {
        return $this->belongsToMany(Notifications::class, 'users_notifications', 'users_id', 'notifications_id');
    }
}

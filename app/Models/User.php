<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'cover_path',
        'profile_picture_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('username')
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Get the posts for the user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the groups owned by the user.
     */
    public function ownedGroups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the groups that the user is a member of.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_users')
            ->withPivot(['status', 'role', 'created_at'])
            ->wherePivot('status', 'approved');
    }

    /**
     * Get pending group join requests for the user.
     */
    public function pendingGroupRequests()
    {
        return $this->belongsToMany(Group::class, 'group_users')
            ->withPivot(['status', 'role', 'created_at'])
            ->wherePivot('status', 'pending');
    }

    /**
     * Get group invitations for the user.
     */
    public function groupInvitations()
    {
        return $this->hasMany(GroupInvitation::class);
    }

    /**
     * Get the albums owned by the user.
     */
    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    /**
     * Get the photos uploaded by the user.
     */
    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Get the photo tags created by the user.
     */
    public function photoTags()
    {
        return $this->hasMany(PhotoTag::class);
    }
}

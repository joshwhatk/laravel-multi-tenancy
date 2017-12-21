<?php

namespace App\Database;

use Auth;
use App\Database\Instance;
use App\Services\UserService;
use App\Services\InstanceService;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Database\Support\MultiDatabaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends MultiDatabaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, SoftDeletes, Notifiable;

    protected $connection = 'base';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'password', 'is_activated', 'organization_id', 'title', 'is_sys_admin'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_activated' => 'boolean',
        'is_sys_admin' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'instance_role_user')->withPivot('instance_id');
    }

    public function instances()
    {
        return $this->belongsToMany(Instance::class, 'instance_role_user')->withPivot('role_id');
    }

    public function instances_roles()
    {
        return $this->hasMany(InstanceRole::class);
    }

    public function instance_role()
    {
        return $this->hasOne(InstanceRole::class)->where('instance_id', InstanceService::make()->id);
    }

    public function organization()
    {
        // belongsTo(RelatedModel, foreignKey = organization_id, keyOnRelatedModel = id)
        return $this->belongsTo(Organization::class);
    }

    public function submissions()
    {
        // hasMany(RelatedModel, foreignKeyOnRelatedModel = user_id, localKey = id)
        return $this->hasMany(Submission::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->attributes['full_name'] = $this->first_name.' '.$this->last_name;
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForInstance($query, InstanceService $instance)
    {
        return $query->whereHas('instances', function($query) use ($instance) {
            return $query->where('slug', $instance->slug);
        });
    }

    public function scopeVisibleForUser($query, User $user)
    {
        if(!is_null($user) && $user->isSysAdmin())
        {
            return $query;
        }

        return $query->forInstance(InstanceService::make());
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($query) use ($search)
        {
            return $query->where('first_name', 'like', '%'.$search.'%')
                ->orWhere('last_name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhereHas('instance_role', function($query) use ($search) {
                    return $query->whereHas('instance', function($query) {
                        return $query->where('slug', InstanceService::make()->slug);
                    })
                    ->whereHas('role', function($query) use ($search) {
                        return $query->where('name', 'like', '%'.$search.'%');
                    });
                })
                ->orWhereHas('organization', function($query) use ($search) {
                    return $query->where('name', 'like', '%'.$search.'%');
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function convertSlugToInstance($slug)
    {
        $instances = Instance::getInstances();

        //-- get the only instance that is currently being viewed
        return $instances->filter(function ($item) use ($slug) {
            return $slug === $item->slug;
        })->first();
    }

    public function isActivated()
    {
        return !! $this->is_activated;
    }

    public function isSysAdmin()
    {
        return !! $this->is_sys_admin;
    }

    public function isAdmin()
    {
        return ($this->hasRole('admin') or $this->isSysAdmin());
    }

    public function isEditor()
    {
        return ($this->hasRole('editor') or $this->isSysAdmin());
    }

    public function canAdministrate()
    {
        return ($this->hasRole('admin') or $this->hasRole('editor') or $this->isSysAdmin());
    }

    public function hasRole($role)
    {
        $instance_role = UserService::make()->instance_role;

        if(is_null($instance_role))
        {
            return false;
        }

        return $instance_role->role->label === $role;
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

<?php

namespace App\Database;

use Cache;
use Exception;
use App\Database\Traits\SluggableTrait as Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;

class Instance extends Model
{
    use SoftDeletes, Sluggable;

    protected $connection = 'base';

    protected $fillable = ['name', 'slug', 'database'];

    protected $hidden = ['database', 'created_at', 'deleted_at', 'updated_at'];

    protected $dates = ['deleted_at'];

    private static $cache_time = 300;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        return $this->belongsToMany(User::class, 'instance_role_user')->withPivot('role_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'instance_role_user')->withPivot('user_id');
    }

    public static function getInstances()
    {
        try {
            if (! Cache::has('instances')) {
                $instances = static::orderBy('name')->get();
                Cache::put('instances', $instances, static::$cache_time);

                return $instances;
            }

            $instances = Cache::get('instances');

            return $instances;
        } catch (Exception $e) {
            return collect([]);
        }
    }

    public static function refreshInstances()
    {
        $instances = static::orderBy('name')->get();
        Cache::put('instances', $instances, static::$cache_time);

        return $instances;
    }
}

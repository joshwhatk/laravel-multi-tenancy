<?php

namespace App\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $connection = 'base';

    protected $dates = ['deleted_at'];

    protected $fillable = ['name', 'label'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'instance_role_user')->withPivot('instance_id');
    }

    public function instances()
    {
        return $this->belongsToMany(Instance::class, 'instance_role_user')->withPivot('user_id');
    }
}

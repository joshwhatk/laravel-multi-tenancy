<?php

namespace App\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstanceRole extends Model
{
    use SoftDeletes;

    protected $connection = 'base';

    protected $table = 'instance_role_user';

    protected $dates = ['deleted_at'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['instance_id', 'role_id', 'user_id'];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updateOrDelete($role_id)
    {
        // 1. trashed and !empty => restore
        // 2. trashed and empty => nothing
        // 3. !trashed and !empty => save
        // 4. !trashed and empty => delete

        if ($this->trashed()) {
            if (empty($role_id)) {
                return $this;
            }

            $this->restore();
        }

        if (empty($role_id)) {
            $this->delete();
            return $this;
        }

        $this->role_id = $role_id;
        $this->save();
        return $this;
    }
}

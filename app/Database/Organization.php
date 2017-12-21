<?php

namespace App\Database;

use App\Database\User;
use App\Database\Request;
use App\Database\Submission;
use App\Database\Support\MultiDatabaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends MultiDatabaseModel
{
    use SoftDeletes;

    protected $connection = 'base';

    protected $dates = ['deleted_at'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Fields that can be mass assigned
     * @link https://laravel.com/docs/eloquent#mass-assignment
     *
     * @var string[]
     */
    protected $fillable = ['name'];

    /**
     * Organization has many Users
     * @link https://laravel.com/docs/eloquent-relationships#one-to-many
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        // hasMany(RelatedModel, foreignKeyOnRelatedModel = organization_id, localKey = id)
        return $this->hasMany(User::class);
    }

    /**
     * Organization has many Submissions
     * @link https://laravel.com/docs/eloquent-relationships#one-to-many
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function submissions()
    {
        // hasMany(RelatedModel, foreignKeyOnRelatedModel = organization_id, localKey = id)
        return $this->hasMany(Submission::class);
    }
}

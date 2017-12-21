<?php

namespace App\Database\Traits;

trait SluggableTrait
{

    public function setNameAttribute($value)
    {
        if (is_null($this->slug)) {
            $this->attributes['slug'] = str_slug($value);
        }
        $this->attributes['name'] = $value;
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public function getNameAttribute()
    {
        return ucfirst($value);
    }

    public function order()
    {
        return $this->hasMany(Order::class);
    }
}

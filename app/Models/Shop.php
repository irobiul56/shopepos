<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $guarded = [];


    public function users()
    {
        return $this->hasMany(User::class);
    }
}

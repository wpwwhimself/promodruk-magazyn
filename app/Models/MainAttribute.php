<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "color",
        "description",
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

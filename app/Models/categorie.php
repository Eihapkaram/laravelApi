<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\product;

class Categorie extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'img','banner'];

    public function product()
    {
        return $this->hasMany(product::class, 'category_id');
    }
}

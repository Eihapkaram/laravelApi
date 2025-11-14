<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\product;
use App\Models\Page;

class Categorie extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'img','banner','page_id'];

    public function product()
    {
        return $this->hasMany(product::class, 'category_id');
    }
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}

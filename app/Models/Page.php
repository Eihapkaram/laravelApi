<?php

namespace App\Models;
use App\Models\product;
use App\Models\categorie;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;
    protected $fillable=['slug','img'];
    public function pageproducts() {
        return $this->hasMany(product::class,'page_id');
    }
     public function categories() {
        return $this->hasMany(categorie::class,'page_id');
    }
}

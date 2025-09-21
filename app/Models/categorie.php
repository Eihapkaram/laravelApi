<?php

namespace App\Models;
use App\Models\product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class categorie extends Model
{
    use HasFactory;
    public function product() {
       return $this->hasMany(product::class,'category_id');
    }
    protected $fillable = ['name','slug','description'];
}

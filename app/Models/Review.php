<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\product;
class Review extends Model
{
    use HasFactory;
    protected $fillable = ['product_id','user_id','comment'];
    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }
    public function product() {
        return $this->belongsTo(product::class,'product_id');
    }

}

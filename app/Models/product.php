<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class product extends Model
{
    protected $fillable = [
        'titel',
        'description',
        'votes',
        'url',
        'img',
        'imegs',
        'updated_at',
        'created_at'
    ];
    use HasFactory;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'site_name',
        'logo',
        'signature',
        'email',
        'facebook',
        'instgrame',
        'twiter',
        'whatsApp',
        'phone1',
        'phone2',
        'hotphone'
    ];
}

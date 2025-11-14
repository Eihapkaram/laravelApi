<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Review;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'img',
        'latitude',   // ✅ أضفت الإحداثيات
        'longitude',
        'security_question',
        'security_answer',
        'wallet_number',
        'front_id_image',
        'back_id_image'
    ];
    public function getOrder()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
    // العملاء التابعين للبائع
    public function customers()
    {
        return $this->belongsToMany(User::class, 'seller_customers', 'seller_id', 'customer_id');
    }

    // البائع اللي يتبعه العميل (عكس العلاقة)
    public function seller()
    {
        return $this->belongsToMany(User::class, 'seller_customers', 'customer_id', 'seller_id');
    }

    public function sales()
    {
        // الطلبات اللي أنشأها المستخدم كـ "بائع"
        return $this->hasMany(Order::class, 'seller_id');
    }
    public function getcart()
    {
        return $this->hasOne(Cart::class);
    }
    public function userReviwes()
    {
        return $this->hasMany(Review::class);
    }
    public function scopeNearby($query, $latitude, $longitude, $distance = 10)
    {
        return $query->selectRaw("*,
            ( 6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance", [$latitude, $longitude, $latitude])
            ->having("distance", "<", $distance)
            ->orderBy("distance", "asc");
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}

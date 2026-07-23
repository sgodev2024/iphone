<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserInfo;
use App\Models\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'manager_id',
        'name',
        'phone',
        'email',
        'password',
        'status',
        'role_id',
        'address',
        'company_name',
        'tax_code',
        'store_name',
        'storage_id',
        'img_url'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['user_info'];

    // Accessor for user info
    public function getUserInfoAttribute()
    {
        return UserInfo::where('user_id', $this->attributes['id'])->first();
    }

    // Relationship with City
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // Relationship with Field
    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    // Relationship with Config
    public function config()
    {
        return $this->hasOne(Config::class);
    }

    // Relationship with Storage
    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }
   public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }
    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }
}

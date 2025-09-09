<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $table = 'wallet';
    protected $fillable = [
        'name',
    ];
    public function userwallet(){
        return $this -> hasOne(UserWallet::class);
    }
    public function user()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
    public function transaction(){
        return $this -> hasMany(Transaction::class, 'wallet_id');
    }
}

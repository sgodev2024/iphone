<?php

namespace App\Models;

use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'code',
        'zip_code',
        'name',
        'phone',
        'email',
        'address',
        'total_money',
        'discount_value',
        'discount_type',
        'payment_method',
        'status',
        'note',
        'created_by'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected $appends = ['orderdetail'];

    public function getOrderdetailAttribute()
    {
        return OrderDetail::where('order_id', $this->attributes['id'])->get();
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

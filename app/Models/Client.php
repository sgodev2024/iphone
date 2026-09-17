<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class Client extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'clients'; // Tên bảng trong cơ sở dữ liệu
    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'phone',
        'zip_code',
        'address',
        'dob',
        'email',
        'gender',
        'clientgroup_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code = generateCode('clients', 'KH');
        });

        static::updating(function ($model): void {
            if ($model->isDirty('branch_id')) {
                throw new LogicException('Cửa hàng của khách hàng không thể thay đổi sau khi tạo.');
            }
        });
    }

    public function customerDebtCollections()
    {
        return $this->hasMany(CustomerDebtCollection::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

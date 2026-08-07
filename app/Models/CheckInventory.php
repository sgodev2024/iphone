<?php

namespace App\Models;

use App\Models\CheckDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInventory extends Model
{
    use HasFactory;
    protected $table = 'check_inventory';
    protected $fillable = ['test_code','user_id', 'note','tong_chenh_lech', 'sl_tang', 'sl_giam'];

    protected $appends = ['checkdetail'];
    public function details()
    {
        return $this->hasMany(CheckDetail::class, 'check_inventory_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getCheckdetailAttribute(){
        return CheckDetail::where('check_inventory_id',$this->attributes['id'])->get();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $latestInventory = self::orderBy('id', 'desc')->first();
            $nextNumber = $latestInventory ? ((int)substr($latestInventory->test_code, 2)) + 1 : 1;
            $model->test_code = 'KH' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}

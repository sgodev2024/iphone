<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDebt extends Model
{
    use HasFactory;
    protected $table = 'customer_debts';

    protected $fillable = [
        'client_id',
        'amount',
        'description',
        'code'
    ];

    protected $appends = ['client', 'detail'];
    public function getClientAttribute(){
        return Client::where('id',$this->attributes['client_id'])->first();
    }

    public function getDetailAttribute(){
        return ClientDebtsDetail::where('customer_debts_id', $this->attributes['id'])->orderBy('created_at', 'desc')->get();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $latastcode = self::orderBy('id', 'desc')->first();
            $nextNumber = $latastcode ? ((int)substr($latastcode->code, 2)) + 1 : 1;
            $model->code = 'CN' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }


}

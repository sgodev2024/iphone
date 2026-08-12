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

    public const PAYMENT_METHOD_CASH = 'cash';

    public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_METHOD_DEBT = 'debt';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_PARTIAL = 'partial';

    public const PAYMENT_STATUS_DEBT = 'debt';

    protected $fillable = [
        'user_id',
        'client_id',
        'branch_id',
        'code',
        'zip_code',
        'name',
        'phone',
        'email',
        'address',
        'receive_address',
        'total_money',
        'discount_value',
        'discount_type',
        'payment_method',
        'paid_amount',
        'debt_amount',
        'payment_status',
        'status', 
        'note',
        'created_by',
        'notification',
    ];

    protected $casts = [
        'status' => 'boolean',
        'total_money' => 'integer',
        'paid_amount' => 'integer',
        'debt_amount' => 'integer',
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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PAID => 'Đã thanh toán',
            self::PAYMENT_STATUS_PARTIAL => 'Thanh toán một phần',
            self::PAYMENT_STATUS_DEBT => 'Còn nợ',
            default => 'Chưa xác định',
        };
    }

    public function paymentStatusBadgeClass(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PAID => 'bg-success',
            self::PAYMENT_STATUS_PARTIAL => 'bg-warning text-dark',
            self::PAYMENT_STATUS_DEBT => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        $snapshotName = trim((string) ($this->attributes['name'] ?? ''));

        if ($snapshotName !== '') {
            return $snapshotName;
        }

        $clientName = trim((string) ($this->client?->name ?? ''));

        return $clientName !== '' ? $clientName : 'Khách lẻ';
    }

    public function getCustomerDisplayPhoneAttribute(): ?string
    {
        return $this->snapshotValueOrClientValue('phone');
    }

    public function getCustomerDisplayEmailAttribute(): ?string
    {
        return $this->snapshotValueOrClientValue('email');
    }

    public function getCustomerDisplayAddressAttribute(): ?string
    {
        foreach (['receive_address', 'address'] as $attribute) {
            $value = trim((string) ($this->attributes[$attribute] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        $clientAddress = trim((string) ($this->client?->address ?? ''));

        return $clientAddress !== '' ? $clientAddress : null;
    }

    private function snapshotValueOrClientValue(string $attribute): ?string
    {
        $snapshotValue = trim((string) ($this->attributes[$attribute] ?? ''));

        if ($snapshotValue !== '') {
            return $snapshotValue;
        }

        $clientValue = trim((string) ($this->client?->{$attribute} ?? ''));

        return $clientValue !== '' ? $clientValue : null;
    }
}

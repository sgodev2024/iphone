<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportCoupon extends Model
{
    use HasFactory;

    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    public const PAYMENT_METHOD_DEBT = 'debt';

    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    protected $table = 'import_coupon';

    protected $fillable = [
        'user_id',
        'supplier_id',
        'companies_id',
        'total',
        'status',
        'coupon_code',
        'payment_ncc',
        'payment_method',
        'paid_amount',
        'debt_amount',
        'payment_status',
        'storage_id',
    ];

    protected $casts = [
        'total' => 'integer',
        'payment_ncc' => 'integer',
        'paid_amount' => 'integer',
        'debt_amount' => 'integer',
    ];

    protected $appends = ['detail', 'user', 'supplier', 'company'];

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_METHOD_CASH,
            self::PAYMENT_METHOD_BANK_TRANSFER,
            self::PAYMENT_METHOD_DEBT,
        ];
    }

    public function getResolvedPaidAmountAttribute(): int
    {
        return (int) ($this->attributes['paid_amount'] ?? $this->attributes['payment_ncc'] ?? 0);
    }

    public function getResolvedDebtAmountAttribute(): int
    {
        if (array_key_exists('debt_amount', $this->attributes) && $this->attributes['debt_amount'] !== null) {
            return (int) $this->attributes['debt_amount'];
        }

        return max((int) ($this->attributes['total'] ?? 0) - $this->resolved_paid_amount, 0);
    }

    public function getResolvedPaymentStatusAttribute(): string
    {
        $status = $this->attributes['payment_status'] ?? null;

        if (in_array($status, [self::PAYMENT_STATUS_PAID, self::PAYMENT_STATUS_PARTIAL, self::PAYMENT_STATUS_UNPAID], true)) {
            return $status;
        }

        $total = (int) ($this->attributes['total'] ?? 0);
        $paidAmount = $this->resolved_paid_amount;

        if ($total > 0 && $paidAmount >= $total) {
            return self::PAYMENT_STATUS_PAID;
        }

        if ($paidAmount > 0) {
            return self::PAYMENT_STATUS_PARTIAL;
        }

        return self::PAYMENT_STATUS_UNPAID;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->resolved_payment_status) {
            self::PAYMENT_STATUS_PAID => 'Đã thanh toán',
            self::PAYMENT_STATUS_PARTIAL => 'Thanh toán một phần',
            default => 'Công nợ',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->attributes['payment_method'] ?? null) {
            self::PAYMENT_METHOD_CASH => 'Tiền mặt',
            self::PAYMENT_METHOD_BANK_TRANSFER => 'Chuyển khoản',
            self::PAYMENT_METHOD_DEBT => 'Công nợ',
            default => 'Chưa xác định',
        };
    }

    public function getDetailAttribute()
    {
        return ImportDetail::where('import_id', $this->attributes['id'])->get();
    }

    public function getUserAttribute()
    {
        return User::where('id', $this->attributes['user_id'])->first();
    }

    public function getSupplierAttribute()
    {
        return Supplier::where('id', $this->attributes['supplier_id'])->first();
    }

    public function getCompanyAttribute()
    {
        return Company::where('id', $this->attributes['companies_id'])->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }

    public function companyRelation(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }

    /**
     * Get the import coupon's details.
     */
    public function details()
    {
        return $this->hasMany(ImportDetail::class, 'import_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $latestCoupon = ImportCoupon::orderBy('id', 'desc')->first();
            $model->coupon_code = 'MP'.str_pad($latestCoupon ? ($latestCoupon->id + 1) : 1, 6, '0', STR_PAD_LEFT);
        });
    }
}

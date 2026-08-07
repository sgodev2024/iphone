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
        $total = max((int) ($this->attributes['total'] ?? 0), 0);

        if (array_key_exists('paid_amount', $this->attributes) && $this->attributes['paid_amount'] !== null) {
            $paidAmount = (int) $this->attributes['paid_amount'];
        } elseif (array_key_exists('payment_ncc', $this->attributes) && $this->attributes['payment_ncc'] !== null) {
            $paidAmount = (int) $this->attributes['payment_ncc'];
        } elseif (array_key_exists('debt_amount', $this->attributes) && $this->attributes['debt_amount'] !== null) {
            $paidAmount = $total - (int) $this->attributes['debt_amount'];
        } else {
            $paidAmount = 0;
        }

        $paidAmount = max($paidAmount, 0);

        return $total > 0 ? min($paidAmount, $total) : $paidAmount;
    }

    public function getResolvedDebtAmountAttribute(): int
    {
        $total = max((int) ($this->attributes['total'] ?? 0), 0);

        if (array_key_exists('debt_amount', $this->attributes) && $this->attributes['debt_amount'] !== null) {
            $debtAmount = max((int) $this->attributes['debt_amount'], 0);

            return $total > 0 ? min($debtAmount, $total) : $debtAmount;
        }

        return max($total - $this->resolved_paid_amount, 0);
    }

    public function getResolvedPaymentStatusAttribute(): string
    {
        $status = $this->attributes['payment_status'] ?? null;

        if (in_array($status, [self::PAYMENT_STATUS_PAID, self::PAYMENT_STATUS_PARTIAL, self::PAYMENT_STATUS_UNPAID], true)) {
            return $status;
        }

        $total = max((int) ($this->attributes['total'] ?? 0), 0);
        $paidAmount = $this->resolved_paid_amount;
        $debtAmount = $this->resolved_debt_amount;

        if ($total > 0 && $paidAmount >= $total && $debtAmount === 0) {
            return self::PAYMENT_STATUS_PAID;
        }

        if ($paidAmount > 0 && ($debtAmount > 0 || $paidAmount < $total)) {
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

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->resolved_payment_status) {
            self::PAYMENT_STATUS_PAID => 'badge-success',
            self::PAYMENT_STATUS_PARTIAL => 'badge-warning',
            default => 'badge-danger',
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
        if ($this->relationLoaded('details')) {
            return $this->getRelation('details');
        }

        return $this->details()->get();
    }

    public function getUserAttribute()
    {
        if ($this->relationLoaded('user')) {
            return $this->getRelation('user');
        }

        return $this->user()->first();
    }

    public function getSupplierAttribute()
    {
        return Supplier::where('id', $this->attributes['supplier_id'])->first();
    }

    public function getCompanyAttribute()
    {
        if ($this->relationLoaded('companyRelation')) {
            return $this->getRelation('companyRelation');
        }

        return $this->companyRelation()->first();
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

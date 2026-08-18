<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankVoucher extends Model
{
    public const DIRECTION_RECEIPT = 'receipt';

    public const DIRECTION_PAYMENT = 'payment';

    public const OPERATION_GENERIC_RECEIPT = 'generic_receipt';

    public const OPERATION_GENERIC_PAYMENT = 'generic_payment';

    public const STATUS_PENDING_ACCOUNTING = 'pending_accounting';

    protected $fillable = [
        'owner_id',
        'voucher_number',
        'direction',
        'operation',
        'transaction_date',
        'bank_account_id',
        'amount',
        'document_type',
        'reference_number',
        'description',
        'attachment',
        'accounting_status',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function counterAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counter_account_id');
    }

    public function accountingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'accounting_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

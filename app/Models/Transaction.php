<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_date',
        'description',
        'reference_number',
        'type',
        'document_type',
        'attachment',
        'created_by',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Lấy entry tài khoản tiền
     */
    public function getMainEntry(array $moneyAccountIds)
    {
        return $this->entries->firstWhere(
            fn($entry) =>
            in_array($entry->account_id, $moneyAccountIds)
        );
    }

    /**
     * Lấy entry tài khoản công nợ đối ứng
     */
    public function getContraEntry(array $moneyAccountIds)
    {
        return $this->entries->firstWhere(
            fn($entry) =>
            !in_array($entry->account_id, $moneyAccountIds)
        );
    }

    protected $casts = [
        'transaction_date' => 'date',
    ];
}

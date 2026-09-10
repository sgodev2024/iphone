<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'phone',
        'address',
        'email',
        'tax_number',
        'bank_account',
        'bank_id',
        'city_id',
        'note',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (Schema::hasTable('branches')
                && Schema::hasColumn('companies', 'branch_id')
                && $company->branch_id === null
            ) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Nhà cung cấp bắt buộc phải thuộc một cửa hàng.'],
                ]);
            }
        });

        static::updating(function (Company $company): void {
            if (! Schema::hasColumn('companies', 'branch_id')
                || ! $company->isDirty('branch_id')
                || (int) $company->getOriginal('branch_id') === (int) $company->branch_id
            ) {
                return;
            }

            if ($company->branch_id === null || $company->hasBranchHistory()) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Không thể chuyển nhà cung cấp đã phát sinh lịch sử sang cửa hàng khác.'],
                ]);
            }
        });
    }

    public function scopeBranchOwned(Builder $query): Builder
    {
        return Schema::hasColumn('companies', 'branch_id')
            ? $query->whereNotNull('companies.branch_id')
            : $query;
    }

    public function hasBranchHistory(): bool
    {
        $references = [
            ['suppliers', 'company_id'],
            ['company_product', 'company_id'],
            ['import_coupon', 'companies_id'],
            ['supplier_debts', 'companies_id'],
            ['expense', 'companies_id'],
            ['supplier_debt_yearly_snapshots', 'company_id'],
            ['supplier_debt_snapshot_states', 'company_id'],
        ];

        foreach ($references as [$table, $column]) {
            if (Schema::hasTable($table)
                && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $this->getKey())->exists()
            ) {
                return true;
            }
        }

        return Schema::hasTable('transaction_entries')
            && Schema::hasColumn('transaction_entries', 'tableable_type')
            && Schema::hasColumn('transaction_entries', 'tableable_id')
            && DB::table('transaction_entries')
                ->where('tableable_type', self::class)
                ->where('tableable_id', $this->getKey())
                ->exists();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function supplier()
    {
        return $this->hasMany(Supplier::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function hasRepresentative()
    {
        return $this->supplier()->exists();
    }

    public function product()
    {
        return $this->belongsToMany(Product::class, 'company_product');
    }

    public function importCoupons(): HasMany
    {
        return $this->hasMany(ImportCoupon::class, 'companies_id');
    }
}

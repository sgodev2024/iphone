<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CompanyProduct extends Model
{
    use HasFactory;

    protected $table = 'company_product';
    protected $fillable = [
        'product_id',
        'company_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (CompanyProduct $link): void {
            if (! Schema::hasColumn('products', 'branch_id')
                || ! Schema::hasColumn('companies', 'branch_id')
            ) {
                return;
            }

            $productBranchId = Product::query()->whereKey($link->product_id)->value('branch_id');
            $companyBranchId = Company::query()->whereKey($link->company_id)->value('branch_id');

            if ($productBranchId === null
                || $companyBranchId === null
                || (int) $productBranchId !== (int) $companyBranchId
            ) {
                throw ValidationException::withMessages([
                    'company_id' => ['Sản phẩm và nhà cung cấp phải thuộc cùng một cửa hàng.'],
                ]);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}

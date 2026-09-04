<?php

namespace App\Http\Requests\Import;

use App\Models\Import;
use App\Models\ImportCoupon;
use App\Models\Product;
use App\Models\ProductImei;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreImportCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->isAdministrator() || $user->isAdminStore()
                || $user->roleKey() === 'warehouse');
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        $paymentMethod = $this->input('payment_method', ImportCoupon::PAYMENT_METHOD_CASH);

        foreach ((array) $this->input('imeis', []) as $importId => $imeis) {
            if (! is_array($imeis)) {
                $normalized[$importId] = $imeis;

                continue;
            }

            $normalized[$importId] = array_map(
                fn($imei) => trim((string) $imei),
                $imeis
            );
        }

        $this->merge([
            'imeis' => $normalized,
            'payment_method' => is_string($paymentMethod) ? trim($paymentMethod) : $paymentMethod,
        ]);
    }

    public function rules(): array
    {
        $ownerId = $this->user()?->ownerId();

        return [
            'supplier' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where(fn ($query) => $query->where('user_id', $ownerId)),
            ],
            'storage' => [
                'required',
                'integer',
                Rule::exists('storages', 'id')->where(fn ($query) => $query->where('user_id', $ownerId)),
            ],
            'total' => ['nullable', 'numeric', 'min:0'],
            'totalncc' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,debt'],
            'bank_account_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => $this->input('payment_method') === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
                    && (int) $this->input('totalncc', 0) > 0),
            ],
            'datetime' => ['nullable', 'date'],
            'imeis' => ['nullable', 'array'],
            'imeis.*' => ['array'],
            'imeis.*.*' => ['required', 'string', 'min:1', 'max:' . ProductImei::IMEI_MAX_LENGTH],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier.required' => 'Vui lòng chọn nhà cung cấp.',
            'supplier.exists' => 'Nhà cung cấp không hợp lệ.',
            'storage.required' => 'Vui lòng chọn kho nhập.',
            'storage.exists' => 'Kho nhập không hợp lệ.',
            'totalncc.numeric' => 'Số tiền trả nhà cung cấp không hợp lệ.',
            'totalncc.min' => 'Số tiền trả nhà cung cấp không được âm.',
            'imeis.array' => 'Danh sách IMEI không hợp lệ.',
            'imeis.*.array' => 'Danh sách IMEI/Serial không hợp lệ.',
            'imeis.*.*.string' => 'IMEI/Serial phải là chuỗi ký tự.',
            'imeis.*.*.min' => 'IMEI/Serial không được để trống.',
            'imeis.*.*.max' => 'IMEI/Serial phải có tối đa 50 ký tự.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateBankAccount($validator);

            $ownerId = (int) $this->user()->ownerId();
            $imports = Import::query()
                ->with('product')
                ->where('quantity', '>', 0)
                ->whereHas('product', function ($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->get();

            if ($imports->isEmpty()) {
                $validator->errors()->add('items', 'Phiếu nhập phải có ít nhất một sản phẩm.');

                return;
            }

            $submitted = (array) $this->input('imeis', []);
            $knownIds = $imports->pluck('id')->map(fn($id) => (string) $id);
            $duplicatedProductId = $imports->pluck('product_id')
                ->duplicates()
                ->first();

            if ($duplicatedProductId !== null) {
                $productName = $imports->firstWhere('product_id', $duplicatedProductId)?->product?->name ?? "#{$duplicatedProductId}";
                $validator->errors()->add('items', "Sản phẩm {$productName} đang bị lặp trong phiếu nhập.");
            }

            foreach (array_keys($submitted) as $importId) {
                if (! $knownIds->contains((string) $importId)) {
                    $validator->errors()->add('imeis', 'Danh sách IMEI chứa dòng sản phẩm không hợp lệ.');
                }
            }

            $seen = [];
            $candidates = [];

            foreach ($imports as $import) {
                $productName = $import->product?->name ?? "#{$import->product_id}";
                $tracking = $import->product?->inventory_tracking;
                $quantity = (int) $import->quantity;
                $rowImeis = $submitted[$import->id] ?? $submitted[(string) $import->id] ?? [];
                $rowImeis = is_array($rowImeis) ? array_values($rowImeis) : [];
                $normalizedRowImeis = collect($rowImeis)
                    ->map(fn($imei) => trim((string) $imei))
                    ->values();

                if (! in_array($tracking, Product::INVENTORY_TRACKING_OPTIONS, true)) {
                    $validator->errors()->add(
                        "items.{$import->id}",
                        "Sản phẩm {$productName} chưa có phương thức quản lý tồn kho hợp lệ."
                    );

                    continue;
                }

                if ($tracking === Product::INVENTORY_TRACKING_QUANTITY) {
                    if ($normalizedRowImeis->filter(fn(string $imei) => $imei !== '')->isNotEmpty()) {
                        $validator->errors()->add(
                            "imeis.{$import->id}",
                            "Sản phẩm {$productName} là sản phẩm thường nên không được gửi danh sách IMEI."
                        );
                    }

                    continue;
                }

                if (count($rowImeis) < $quantity) {
                    $missing = $quantity - count($rowImeis);
                    $validator->errors()->add(
                        "imeis.{$import->id}",
                        "Sản phẩm {$productName} còn thiếu {$missing} IMEI."
                    );
                } elseif (count($rowImeis) > $quantity) {
                    $validator->errors()->add(
                        "imeis.{$import->id}",
                        "Số lượng IMEI của sản phẩm {$productName} vượt quá số lượng nhập."
                    );
                }

                foreach (range(0, max($quantity - 1, 0)) as $index) {
                    if ($quantity === 0) {
                        break;
                    }

                    $position = $index + 1;
                    $path = "imeis.{$import->id}.{$index}";
                    $imei = $rowImeis[$index] ?? '';

                    if ($imei === '') {
                        $validator->errors()->add(
                            $path,
                            "IMEI/Serial máy số {$position} của sản phẩm {$productName} không được để trống."
                        );

                        continue;
                    }

                    if (mb_strlen($imei) > ProductImei::IMEI_MAX_LENGTH) {
                        $validator->errors()->add(
                            $path,
                            "IMEI/Serial máy số {$position} của sản phẩm {$productName} phải có tối đa 50 ký tự."
                        );

                        continue;
                    }

                    if (isset($seen[$imei])) {
                        $validator->errors()->add(
                            $path,
                        "Mã IMEI/Serial {$imei} của sản phẩm {$productName} bị trùng trong cùng phiếu nhập."
                        );
                    } else {
                        $seen[$imei] = $path;
                    }

                    $candidates[$path] = [
                        'imei' => $imei,
                        'product_name' => $productName,
                    ];
                }
            }

            if ($candidates === []) {
                return;
            }

            $existingImeis = ProductImei::query()
                ->withTrashed()
                ->whereIn('imei', collect($candidates)->pluck('imei')->all())
                ->pluck('imei')
                ->flip();

            foreach ($candidates as $path => $candidate) {
                if ($existingImeis->has($candidate['imei'])) {
                    $validator->errors()->add(
                        $path,
                        "Mã IMEI/Serial {$candidate['imei']} của sản phẩm {$candidate['product_name']} đã tồn tại trong kho."
                    );
                }
            }
        });
    }

    private function validateBankAccount(Validator $validator): void
    {
        if ($this->input('payment_method') !== ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
            || (int) $this->input('totalncc', 0) <= 0) {
            return;
        }

        if (! $this->filled('bank_account_id')) {
            $validator->errors()->add(
                'bank_account_id',
                'Vui lòng chọn tài khoản ngân hàng con khi thanh toán chuyển khoản.'
            );

            return;
        }

        if (! Schema::hasTable('accounts')
            || ! Schema::hasColumn('accounts', 'parent_id')
            || ! Schema::hasColumn('accounts', 'status')
            || ! Schema::hasColumn('accounts', 'is_default')) {
            $validator->errors()->add(
                'bank_account_id',
                'Hệ thống chưa có cấu hình tài khoản ngân hàng con dưới 112.'
            );

            return;
        }

        $parentId = \App\Models\Account::query()->where('code', '112')->value('id');
        $valid = $parentId && \App\Models\Account::query()
            ->whereKey((int) $this->input('bank_account_id'))
            ->where('parent_id', $parentId)
            ->where('status', true)
            ->where('is_default', false)
            ->exists();

        if (! $valid) {
            $validator->errors()->add(
                'bank_account_id',
                'Tài khoản ngân hàng phải là tài khoản con đang hoạt động của 112.'
            );
        }
    }
}

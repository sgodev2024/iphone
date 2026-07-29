<?php

namespace App\Http\Requests\Import;

use App\Models\Import;
use App\Models\ImportCoupon;
use App\Models\Product;
use App\Models\ProductImei;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreImportCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array((int) $this->user()->role_id, [1, 2, 4], true);
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
        return [
            'supplier' => ['required', 'integer', 'exists:companies,id'],
            'storage' => ['required', 'integer', 'exists:storages,id'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'totalncc' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,debt'],
            'imeis' => ['nullable', 'array'],
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ownerIds = collect([$this->user()?->id, $this->user()?->manager_id])
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $imports = Import::query()
                ->with('product')
                ->where('quantity', '>', 0)
                ->whereHas('product', function ($query) use ($ownerIds) {
                    $query->whereIn('user_id', $ownerIds);
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
                $hasSubmittedImeis = array_key_exists($import->id, $submitted)
                    || array_key_exists((string) $import->id, $submitted);

                if (! in_array($tracking, Product::INVENTORY_TRACKING_OPTIONS, true)) {
                    $validator->errors()->add(
                        "items.{$import->id}",
                        "Sản phẩm {$productName} chưa có phương thức quản lý tồn kho hợp lệ."
                    );

                    continue;
                }

                if ($tracking === Product::INVENTORY_TRACKING_QUANTITY) {
                    if ($hasSubmittedImeis) {
                        $validator->errors()->add(
                            "imeis.{$import->id}",
                            "Sản phẩm {$productName} là sản phẩm thường nên không được gửi danh sách IMEI."
                        );
                    }

                    continue;
                }

                if ($quantity > ProductImei::MAX_IMPORT_QUANTITY) {
                    $validator->errors()->add(
                        "imeis.{$import->id}",
                        'Mỗi lần chỉ được nhập tối đa 35 sản phẩm'
                    );

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
                            "Vui lòng nhập IMEI cho máy số {$position} của sản phẩm {$productName}."
                        );

                        continue;
                    }

                    if (preg_match('/^\d{15}$/D', $imei) !== 1) {
                        $validator->errors()->add(
                            $path,
                            "IMEI máy số {$position} của sản phẩm {$productName} phải gồm đúng 15 chữ số."
                        );

                        continue;
                    }

                    if (isset($seen[$imei])) {
                        $validator->errors()->add(
                            $path,
                            "IMEI {$imei} của sản phẩm {$productName} bị trùng trong cùng phiếu nhập."
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
                ->whereIn('imei', collect($candidates)->pluck('imei')->all())
                ->pluck('imei')
                ->flip();

            foreach ($candidates as $path => $candidate) {
                if ($existingImeis->has($candidate['imei'])) {
                    $validator->errors()->add(
                        $path,
                        "IMEI {$candidate['imei']} của sản phẩm {$candidate['product_name']} đã tồn tại trong hệ thống."
                    );
                }
            }
        });
    }
}

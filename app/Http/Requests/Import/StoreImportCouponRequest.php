<?php

namespace App\Http\Requests\Import;

use App\Models\Import;
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

        foreach ((array) $this->input('imeis', []) as $importId => $imeis) {
            if (! is_array($imeis)) {
                $normalized[$importId] = $imeis;

                continue;
            }

            $normalized[$importId] = array_map(
                fn ($imei) => trim((string) $imei),
                $imeis
            );
        }

        $this->merge(['imeis' => $normalized]);
    }

    public function rules(): array
    {
        return [
            'supplier' => ['required', 'integer', 'exists:companies,id'],
            'storage' => ['required', 'integer', 'exists:storages,id'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'totalncc' => ['nullable', 'numeric', 'min:0'],
            'imeis' => ['required', 'array'],
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
            'imeis.required' => 'Vui lòng nhập đầy đủ IMEI cho các sản phẩm.',
            'imeis.array' => 'Danh sách IMEI không hợp lệ.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $imports = Import::query()
                ->with('product')
                ->where('quantity', '>', 0)
                ->get();

            if ($imports->isEmpty()) {
                $validator->errors()->add('items', 'Phiếu nhập phải có ít nhất một sản phẩm.');

                return;
            }

            $submitted = (array) $this->input('imeis', []);
            $knownIds = $imports->pluck('id')->map(fn ($id) => (string) $id);

            foreach (array_keys($submitted) as $importId) {
                if (! $knownIds->contains((string) $importId)) {
                    $validator->errors()->add('imeis', 'Danh sách IMEI chứa dòng sản phẩm không hợp lệ.');
                }
            }

            $seen = [];
            $candidates = [];

            foreach ($imports as $import) {
                $productName = $import->product?->name ?? "#{$import->product_id}";
                $quantity = (int) $import->quantity;
                $rowImeis = $submitted[$import->id] ?? $submitted[(string) $import->id] ?? [];
                $rowImeis = is_array($rowImeis) ? array_values($rowImeis) : [];

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
                            "IMEI {$imei} bị trùng trong cùng phiếu nhập."
                        );
                    } else {
                        $seen[$imei] = $path;
                    }

                    $candidates[$path] = $imei;
                }
            }

            if ($candidates === []) {
                return;
            }

            $existingImeis = ProductImei::query()
                ->whereIn('imei', array_values($candidates))
                ->pluck('imei')
                ->flip();

            foreach ($candidates as $path => $imei) {
                if ($existingImeis->has($imei)) {
                    $validator->errors()->add($path, "IMEI {$imei} đã tồn tại trong hệ thống.");
                }
            }
        });
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Services\BarcodePrintService;
use App\Services\InternalBarcodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportBarcodeController extends Controller
{
    public const MAX_PRODUCT_LABEL_QUANTITY = 1000;

    public function __construct(
        private readonly BarcodePrintService $barcodePrintService,
        private readonly InternalBarcodeService $internalBarcodeService,
    ) {}

    public function index(int $id): View
    {
        $this->findImportCoupon($id);

        return view('admin.Importproduct.barcodes.index', [
            'importCouponId' => $id,
            'items' => $this->barcodeItems($id),
            'maxProductLabelQuantity' => self::MAX_PRODUCT_LABEL_QUANTITY,
        ]);
    }

    public function print(Request $request, int $id): View|RedirectResponse
    {
        $this->findImportCoupon($id);

        $validated = $request->validate([
            'single_imei_id' => ['nullable', 'integer', 'min:1'],
            'single_product_detail_id' => ['nullable', 'integer', 'min:1'],
            'print_all' => ['nullable', 'boolean'],
            'imei_ids' => ['nullable', 'array'],
            'imei_ids.*' => ['integer', 'min:1', 'distinct'],
            'product_detail_ids' => ['nullable', 'array'],
            'product_detail_ids.*' => ['integer', 'min:1', 'distinct'],
            'product_label_quantities' => ['nullable', 'array'],
            'product_label_quantities.*' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . self::MAX_PRODUCT_LABEL_QUANTITY,
            ],
        ]);

        $items = $this->barcodeItems($id);

        if ($items->isEmpty()) {
            return back()->withErrors([
                'labels' => 'Phiếu nhập này không có sản phẩm đủ điều kiện in tem.',
            ]);
        }

        $selectedItems = $this->selectedItems($items, $validated, $request);

        if ($selectedItems instanceof RedirectResponse) {
            return $selectedItems;
        }

        $labels = $this->labelsForItems(
            $selectedItems,
            $validated['product_label_quantities'] ?? []
        );

        if ($labels instanceof RedirectResponse) {
            return $labels;
        }

        $this->recordImeiPrints($selectedItems);

        return view('admin.Importproduct.barcodes.print', [
            'labels' => $labels,
            'importCouponId' => $id,
        ]);
    }

    private function findImportCoupon(int $id): ImportCoupon
    {
        $query = ImportCoupon::query();

        if ($user = Auth::user()) {
            $query->where('user_id', (int) $user->ownerId());
        }

        return $query->findOrFail($id);
    }

    private function barcodeItems(int $importCouponId): Collection
    {
        return ImportDetail::query()
            ->with([
                'product:id,name,code,barcode,price,inventory_tracking',
                'imeis' => function ($query): void {
                    $query
                        ->where('status', ProductImei::STATUS_IN_STOCK)
                        ->whereNotNull('barcode')
                        ->orderBy('id');
                },
            ])
            ->where('import_id', $importCouponId)
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get()
            ->flatMap(function (ImportDetail $detail): array|Collection {
                $product = $detail->product;

                if (! $product) {
                    return [];
                }

                if ($product->isImeiTracked()) {
                    return $detail->imeis->map(
                        fn(ProductImei $imei): array => $this->imeiItem($detail, $product, $imei)
                    );
                }

                if (! $product->isQuantityTracked()) {
                    return [];
                }

                return [$this->productItem($detail, $product)];
            })
            ->values();
    }

    private function imeiItem(
        ImportDetail $detail,
        Product $product,
        ProductImei $imei
    ): array {
        return [
            'type' => 'imei',
            'type_label' => 'Tem thiết bị IMEI',
            'id' => (int) $imei->id,
            'import_detail_id' => (int) $detail->id,
            'product_id' => (int) $product->id,
            'product_name' => $product->name,
            'product_price' => (int) $product->price,
            'imei' => $imei->imei,
            'barcode' => $imei->barcode,
            'import_quantity' => (int) $detail->quantity,
            'default_label_quantity' => 1,
            'max_label_quantity' => 1,
            'printed_at' => $imei->printed_at,
            'print_count' => (int) ($imei->print_count ?? 0),
        ];
    }

    private function productItem(ImportDetail $detail, Product $product): array
    {
        $barcode = $this->internalBarcodeService->resolveProductBarcode($product);
        $importQuantity = (int) $detail->quantity;
        $maxLabelQuantity = min($importQuantity, self::MAX_PRODUCT_LABEL_QUANTITY);

        return [
            'type' => 'product',
            'type_label' => 'Tem sản phẩm',
            'id' => (int) $detail->id,
            'import_detail_id' => (int) $detail->id,
            'product_id' => (int) $product->id,
            'product_name' => $product->name,
            'product_price' => (int) $product->price,
            'imei' => null,
            'barcode' => $barcode,
            'import_quantity' => $importQuantity,
            'default_label_quantity' => $maxLabelQuantity,
            'max_label_quantity' => $maxLabelQuantity,
            'printed_at' => null,
            'print_count' => null,
        ];
    }

    private function selectedItems(
        Collection $items,
        array $validated,
        Request $request
    ): Collection|RedirectResponse {
        if (! empty($validated['single_imei_id'])) {
            return $this->selectSingleItem(
                $items,
                'imei',
                (int) $validated['single_imei_id']
            );
        }

        if (! empty($validated['single_product_detail_id'])) {
            return $this->selectSingleItem(
                $items,
                'product',
                (int) $validated['single_product_detail_id']
            );
        }

        if ($request->boolean('print_all')) {
            return $items;
        }

        $imeiIds = collect($validated['imei_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
        $productDetailIds = collect($validated['product_detail_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($imeiIds->isEmpty() && $productDetailIds->isEmpty()) {
            return back()->withErrors([
                'labels' => 'Bạn chưa chọn tem cần in.',
            ]);
        }

        $selected = $items
            ->filter(function (array $item) use ($imeiIds, $productDetailIds): bool {
                if ($item['type'] === 'imei') {
                    return $imeiIds->contains($item['id']);
                }

                return $productDetailIds->contains($item['id']);
            })
            ->values();

        if ($selected->count() !== ($imeiIds->count() + $productDetailIds->count())) {
            return back()->withErrors([
                'labels' => 'Có tem không thuộc phiếu nhập này hoặc không đủ điều kiện in.',
            ]);
        }

        return $selected;
    }

    private function selectSingleItem(
        Collection $items,
        string $type,
        int $id
    ): Collection|RedirectResponse {
        $item = $items->first(
            fn(array $item): bool => $item['type'] === $type && $item['id'] === $id
        );

        if (! $item) {
            return back()->withErrors([
                'labels' => 'Tem không thuộc phiếu nhập này hoặc không đủ điều kiện in.',
            ]);
        }

        return collect([$item]);
    }

    private function labelsForItems(
        Collection $items,
        array $productLabelQuantities
    ): Collection|RedirectResponse {
        $labels = collect();

        foreach ($items as $item) {
            if ($item['type'] === 'imei') {
                $labels->push($this->makeLabel($item));

                continue;
            }

            $quantity = $this->productLabelQuantity($item, $productLabelQuantities);

            if ($quantity instanceof RedirectResponse) {
                return $quantity;
            }

            foreach (range(1, $quantity) as $copyNumber) {
                $labels->push($this->makeLabel($item, $copyNumber, $quantity));
            }
        }

        return $labels;
    }

    private function productLabelQuantity(
        array $item,
        array $productLabelQuantities
    ): int|RedirectResponse {
        $rawQuantity = $productLabelQuantities[$item['id']]
            ?? $item['default_label_quantity'];
        $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT);

        if ($quantity === false) {
            return back()->withErrors([
                'product_label_quantities.' . $item['id'] => 'Số tem cần in không hợp lệ.',
            ]);
        }

        if ($quantity < 1 || $quantity > $item['max_label_quantity']) {
            return back()->withErrors([
                'product_label_quantities.' . $item['id'] =>
                "Số tem của {$item['product_name']} phải từ 1 đến {$item['max_label_quantity']}.",
            ]);
        }

        return $quantity;
    }

    private function makeLabel(
        array $item,
        int $copyNumber = 1,
        int $copyTotal = 1
    ): array {
        return [
            'type' => $item['type'],
            'type_label' => $item['type_label'],
            'id' => $item['id'],
            'product_name' => $item['product_name'],
            'product_price' => $item['product_price'],
            'imei' => $item['imei'],
            'barcode' => $item['barcode'],
            'barcode_svg' => $this->barcodePrintService->generateSvg($item['barcode']),
            'copy_number' => $copyNumber,
            'copy_total' => $copyTotal,
        ];
    }

    private function recordImeiPrints(Collection $items): void
    {
        $imeiIds = $items
            ->where('type', 'imei')
            ->pluck('id')
            ->unique()
            ->values();

        if ($imeiIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($imeiIds): void {
            ProductImei::query()
                ->whereIn('id', $imeiIds->all())
                ->update([
                    'printed_at' => now(),
                    'print_count' => DB::raw('print_count + 1'),
                ]);
        });
    }
}

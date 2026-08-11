<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductImeiController extends Controller
{
    public function globalIndex(Request $request): View
    {
        $filters = [
            'imei' => trim((string) $request->query('imei', '')),
            'product' => trim((string) $request->query('product', '')),
            'status' => trim((string) $request->query('status', '')),
            'company_id' => trim((string) $request->query('company_id', '')),
            'coupon_code' => trim((string) $request->query('coupon_code', '')),
            'from_date' => trim((string) $request->query('from_date', '')),
            'to_date' => trim((string) $request->query('to_date', '')),
        ];

        [$fromDate, $toDate, $filterWarning] = $this->resolveDateRange(
            $filters['from_date'],
            $filters['to_date']
        );

        $imeiBaseQuery = ProductImei::query()
            ->whereHas('product', function (Builder $productQuery) {
                $productQuery->where(
                    'inventory_tracking',
                    Product::INVENTORY_TRACKING_IMEI
                );
            });

        $statistics = (clone $imeiBaseQuery)
            ->selectRaw(
                'COUNT(*) as total, '
                    . 'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as in_stock, '
                    . 'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as sold',
                [
                    ProductImei::STATUS_IN_STOCK,
                    ProductImei::STATUS_SOLD,
                ]
            )
            ->first();

        $statistics = [
            'total' => (int) ($statistics->total ?? 0),
            'in_stock' => (int) ($statistics->in_stock ?? 0),
            'sold' => (int) ($statistics->sold ?? 0),
        ];

        $statistics['other'] =
            $statistics['total']
            - $statistics['in_stock']
            - $statistics['sold'];

        $imeis = (clone $imeiBaseQuery)
            ->with([
                'product:id,code,name,inventory_tracking',
                'importDetail:id,import_id,price',
                'importDetail.import:id,companies_id,coupon_code,created_at',
                'importDetail.import.companyRelation:id,name',
            ]);

        $this->applyGlobalFilters(
            $imeis,
            $filters,
            $fromDate,
            $toDate
        );

        $imeis = $imeis
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $companies = Company::query()
            ->whereHas(
                'importCoupons.details.imeis.product',
                function (Builder $productQuery) {
                    $productQuery->where(
                        'inventory_tracking',
                        Product::INVENTORY_TRACKING_IMEI
                    );
                }
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = [
            ProductImei::STATUS_IN_STOCK => 'Đang tồn kho',
            ProductImei::STATUS_SOLD => 'Đã bán',
        ];

        $title = 'Quản lý IMEI';

        return view('admin.imeis.index', compact(
            'title',
            'filters',
            'statistics',
            'imeis',
            'companies',
            'statusOptions',
            'filterWarning'
        ));
    }

    public function index(Request $request, Product $product): View
    {
        $this->ensureProductBelongsToCurrentUser($product);

        $search = trim((string) $request->query('search', ''));
        $notImeiTracked = ! $product->isImeiTracked();

        if ($notImeiTracked) {
            $product->setAttribute('imei_stock_count', 0);

            $imeis = new LengthAwarePaginator(
                [],
                0,
                10
            );

            $title = "Quản lý IMEI – {$product->name}";

            return view(
                'admin.product.imeis.index',
                compact(
                    'title',
                    'product',
                    'imeis',
                    'search',
                    'notImeiTracked'
                )
            );
        }

        $product->loadCount([
            'imeis as imei_stock_count' => fn($query) => $query->inStock(),
        ]);

        $imeis = $product->imeis()
            ->with([
                'importDetail.import.companyRelation',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(
                    'imei',
                    'like',
                    "%{$search}%"
                );
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $title = "Quản lý IMEI – {$product->name}";
        $notImeiTracked = false;

        return view(
            'admin.product.imeis.index',
            compact(
                'title',
                'product',
                'imeis',
                'search',
                'notImeiTracked'
            )
        );
    }

    public function destroy(
        Request $request,
        ProductImei $productImei
    ): RedirectResponse {
        $validated = $request->validate([
            'delete_reason' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'delete_reason.required' => 'Vui lòng nhập lý do xóa IMEI.',
            'delete_reason.max' => 'Lý do xóa không được vượt quá 500 ký tự.',
        ]);

        DB::transaction(function () use ($productImei, $validated) {
            $imei = ProductImei::query()
                ->with('importDetail.import')
                ->lockForUpdate()
                ->findOrFail($productImei->id);

            if ($imei->status !== ProductImei::STATUS_IN_STOCK) {
                throw ValidationException::withMessages([
                    'imei' => 'Chỉ được xóa IMEI đang tồn kho.',
                ]);
            }

            // Tự lấy kho từ phiếu nhập, không lấy từ form
            $storageId = $imei->importDetail?->import?->storage_id;

            if (! $storageId) {
                throw ValidationException::withMessages([
                    'imei' => 'Không xác định được kho nhập của IMEI.',
                ]);
            }

            $productStorage = ProductStorage::query()
                ->where('product_id', $imei->product_id)
                ->where('storage_id', $storageId)
                ->lockForUpdate()
                ->first();

            if (
                ! $productStorage
                || (int) $productStorage->quantity <= 0
            ) {
                throw ValidationException::withMessages([
                    'imei' => 'Số lượng tồn kho không hợp lệ, không thể xóa IMEI.',
                ]);
            }

            $productStorage->decrement('quantity');

            $totalQuantity = ProductStorage::query()
                ->where('product_id', $imei->product_id)
                ->sum('quantity');

            Product::query()
                ->whereKey($imei->product_id)
                ->update([
                    'quantity' => $totalQuantity,
                ]);

            $imei->forceFill([
                'deleted_by' => Auth::id(),
                'delete_reason' => $validated['delete_reason'],
            ])->save();

            $imei->delete();
        });

        return back()->with(
            'success',
            'Đã xóa IMEI khỏi tồn kho.'
        );
    }

    private function ensureProductBelongsToCurrentUser(
        Product $product
    ): void {
        abort_unless(
            (int) $product->user_id === (int) Auth::id(),
            404
        );
    }

    private function applyGlobalFilters(
        Builder $query,
        array $filters,
        ?string $fromDate,
        ?string $toDate
    ): void {
        if ($filters['imei'] !== '') {
            $imei = $filters['imei'];
            $query->where(function (Builder $imeiQuery) use ($imei) {
                $imeiQuery
                    ->where('imei', $imei)
                    ->orWhere('imei', 'like', "%{$imei}%");
            });
        }

        if ($filters['product'] !== '') {
            $product = $filters['product'];

            $query->whereHas(
                'product',
                function (Builder $productQuery) use ($product) {
                    $productQuery
                        ->where(
                            'inventory_tracking',
                            Product::INVENTORY_TRACKING_IMEI
                        )
                        ->where(
                            function (Builder $searchQuery) use ($product) {
                                $searchQuery
                                    ->where(
                                        'code',
                                        'like',
                                        "%{$product}%"
                                    )
                                    ->orWhere(
                                        'name',
                                        'like',
                                        "%{$product}%"
                                    );
                            }
                        );
                }
            );
        }

        if ($filters['status'] !== '') {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (
            $filters['company_id'] !== ''
            && ctype_digit($filters['company_id'])
        ) {
            $query->whereHas(
                'importDetail.import.companyRelation',
                function (Builder $companyQuery) use ($filters) {
                    $companyQuery->whereKey(
                        (int) $filters['company_id']
                    );
                }
            );
        }

        if ($filters['coupon_code'] !== '') {
            $couponCode = $filters['coupon_code'];

            $query->whereHas(
                'importDetail.import',
                function (Builder $importQuery) use ($couponCode) {
                    $importQuery->where(
                        'coupon_code',
                        'like',
                        "%{$couponCode}%"
                    );
                }
            );
        }

        if ($fromDate || $toDate) {
            $query->whereHas(
                'importDetail.import',
                function (Builder $importQuery) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $importQuery->whereDate(
                            'created_at',
                            '>=',
                            $fromDate
                        );
                    }

                    if ($toDate) {
                        $importQuery->whereDate(
                            'created_at',
                            '<=',
                            $toDate
                        );
                    }
                }
            );
        }
    }

    private function resolveDateRange(
        string $fromDate,
        string $toDate
    ): array {
        $parsedFromDate = $this->parseDate($fromDate);
        $parsedToDate = $this->parseDate($toDate);
        $filterWarning = null;

        if (
            ($fromDate !== '' && ! $parsedFromDate)
            || ($toDate !== '' && ! $parsedToDate)
        ) {
            $filterWarning =
                'Ngày lọc không hợp lệ. Vui lòng chọn ngày theo định dạng hợp lệ.';
        }

        if (
            $parsedFromDate
            && $parsedToDate
            && $parsedFromDate > $parsedToDate
        ) {
            $filterWarning =
                'Từ ngày phải nhỏ hơn hoặc bằng đến ngày.';

            $parsedFromDate = null;
            $parsedToDate = null;
        }

        return [
            $parsedFromDate,
            $parsedToDate,
            $filterWarning,
        ];
    }

    private function parseDate(string $value): ?string
    {
        if (
            $value === ''
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1
        ) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat(
                'Y-m-d',
                $value
            );

            return $date->format('Y-m-d') === $value
                ? $value
                : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

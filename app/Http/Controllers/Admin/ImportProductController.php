<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Import;
use App\Models\ImportCoupon;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\CompanyService;
use App\Services\ImportProductService;
use App\Services\ProductService;
use App\Services\StorageService;
use App\Services\SupplierPaymentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ImportProductController extends Controller
{
    protected $productService;

    protected $categoryService;

    protected $importProductService;

    protected $companyService;

    protected $storageService;

    public function __construct(ProductService $productService, CategoryService $categoryService, ImportProductService $importProductService, CompanyService $companyService, StorageService $storageService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->importProductService = $importProductService;
        $this->companyService = $companyService;
        $this->storageService = $storageService;
    }

    public function index(Request $request)
    {
        $title = 'Nhập hàng';
        $search = $request->input('search');
        $ownerId = (int) $request->user()->ownerId();
        $companyId = $request->filled('company_id') ? $request->integer('company_id') : null;
        $paymentStatus = $this->normalizePaymentStatus($request->query('payment_status'));
        $outstandingOnly = $paymentStatus === null && $request->boolean('outstanding_only');
        $import = $this->importProductService->getImportCoupon(
            10,
            $search,
            $ownerId,
            $companyId,
            $paymentStatus,
            $outstandingOnly
        );
        $companies = $this->companyService->getCompanyOptionsForOwner($ownerId);
        $paymentStatusOptions = ImportCoupon::paymentStatusFilterOptions();

        return view('admin.Importproduct.index', compact(
            'title',
            'import',
            'search',
            'companies',
            'companyId',
            'paymentStatus',
            'outstandingOnly',
            'paymentStatusOptions'
        ));
    }

    public function bulkDelete(Request $request)
    {
        $user = Auth::user();

        if (! $user || (! $user->isAdministrator() && ! $user->isAdminStore() && $user->roleKey() !== 'warehouse')) {
            return response()->json([
                'message' => 'Bạn không có quyền xóa phiếu nhập.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:import_coupon,id'],
        ]);

        $ids = collect($validated['ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        try {
            $deletedCount = $this->importProductService->deleteImportCoupons($ids, Auth::user());

            return response()->json([
                'message' => "Đã xóa thành công {$deletedCount} phiếu nhập.",
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            Log::error('Failed to bulk delete import coupons.', [
                'ids' => $ids,
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau!',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importdetail(Request $request, $id, SupplierPaymentService $supplierPaymentService)
    {
        $title = 'Thông tin hóa đơn';
        $importdetail = $this->importProductService->getImportCouponByid(
            $id,
            (int) $request->user()->ownerId()
        );
        $paymentSummary = null;
        $paymentUnavailable = null;
        $bankAccounts = collect();
        $paymentIdempotencyKey = (string) Str::uuid();

        try {
            $paymentSummary = $supplierPaymentService->summary($request->user(), (int) $id);

            if ($request->user()->hasPermission('expense.create') && $paymentSummary['remaining'] > 0) {
                $bankAccounts = $supplierPaymentService->bankAccounts();
            }
        } catch (ValidationException $exception) {
            $paymentUnavailable = $exception->validator->errors()->first();
        }

        return view('admin.Importproduct.detail', compact(
            'title',
            'importdetail',
            'paymentSummary',
            'paymentUnavailable',
            'bankAccounts',
            'paymentIdempotencyKey'
        ));
    }

    public function add(Request $request)
    {
        $title = 'Nhập hàng';
        $ownerIds = $this->inventoryOwnerIds();
        $productQueryWarning = null;
        $products = Product::query()
            ->whereIn('user_id', $ownerIds)
            ->latest()
            ->get();
        $category = $this->categoryService->getCategoryAllStaff();
        $user = Auth::user();
        $supplier = $this->companyService->getCompanyForOwner((int) $user->ownerId());
        $storage = $this->storageService->getAllStorage();
        $bankAccounts = collect();

        if (Schema::hasTable('accounts')
            && Schema::hasColumn('accounts', 'parent_id')
            && Schema::hasColumn('accounts', 'status')
            && Schema::hasColumn('accounts', 'is_default')) {
            $bankAccounts = Account::query()
                ->whereHas('parent', fn ($query) => $query->where('code', '112'))
                ->where('is_default', false)
                ->where('status', true)
                ->orderBy('code')
                ->get();
        }

        if ($request->query->has('product_id')) {
            $this->clearCurrentImportItems();

            $preselectedProduct = Product::query()
                ->whereIn('user_id', $ownerIds)
                ->find($request->integer('product_id'));

            if ($preselectedProduct) {
                Import::query()->updateOrCreate(
                    ['product_id' => $preselectedProduct->id],
                    [
                        'quantity' => 1,
                        'price' => $preselectedProduct->price,
                        'total' => $preselectedProduct->price,
                    ]
                );
            } else {
                $productQueryWarning = 'Sản phẩm cần nhập không tồn tại hoặc bạn không có quyền nhập sản phẩm này.';
            }
        }

        return view('admin.Importproduct.add', compact('products', 'user', 'supplier', 'category', 'storage', 'bankAccounts', 'title', 'productQueryWarning'));
    }

    public function importadd(Request $request)
    {
        $validated = $request->validate([
            'product' => ['required', 'integer', 'exists:products,id'],
        ]);
        $productId = (int) $validated['product'];
        $product = Product::query()
            ->whereIn('user_id', $this->inventoryOwnerIds())
            ->find($productId);

        if (! $product) {
            return response()->json([
                'error' => 'Sản phẩm không hợp lệ hoặc bạn không có quyền nhập sản phẩm này.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $products = $this->stagingImportQuery()
            ->where('product_id', $productId)
            ->first();
        if (! $products) {
            Import::create([
                'product_id' => $productId,
                'quantity' => 1,
                'price' => $product->price,
                'total' => $product->price,
            ]);
        }

        return response()->json($this->currentImportPayload());
    }

    public function importupdate(Request $request)
    {
        $validated = $request->validate(
            [
                'dataId' => ['required', 'integer', 'exists:import,id'],
                'value' => ['required', 'integer', 'min:1'],
            ],
            [
                'value.min' => 'Số lượng nhập phải lớn hơn 0.',
            ]
        );
        $import = $this->stagingImportQuery()
            ->whereKey($validated['dataId'])
            ->firstOrFail();

        $import->update([
            'quantity' => $validated['value'],
            'total' => $import->price * $validated['value'],
        ]);

        return response()->json($this->currentImportPayload());
    }

    public function importupdateprice(Request $request)
    {
        $validated = $request->validate([
            'dataId' => ['required', 'integer', 'exists:import,id'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);
        $import = $this->stagingImportQuery()
            ->whereKey($validated['dataId'])
            ->firstOrFail();
        $import->update([
            'price' => $validated['value'],
            'total' => $import->quantity * $validated['value'],
        ]);

        return response()->json($this->currentImportPayload());
    }

    public function importdelete(Request $request)
    {
        $id = $request->id;
        $import = $this->stagingImportQuery()
            ->whereKey($id)
            ->first();

        if (! $import) {
            return response()->json([
                'error' => 'Dòng sản phẩm không hợp lệ hoặc đã được xóa.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $import->delete();

        return response()->json($this->currentImportPayload());
    }

    public function listImport()
    {
        $category = $this->categoryService->getCategoryAllStaff();
        $payload = $this->currentImportPayload();

        return response()->json([
            'import' => $payload['import'],
            'category' => $category,
            'total' => $payload['total'],
        ]);
    }

    public function addCategory(Request $request)
    {
        $validated = $request->validate([
            'selectedValues' => ['required', 'array', 'min:1'],
            'selectedValues.*' => ['required', 'integer', 'exists:categories,id'],
        ]);
        $imports = $this->stagingImportQuery()->get();
        $products = Product::query()
            ->whereIn('user_id', $this->inventoryOwnerIds())
            ->whereIn('category_id', $validated['selectedValues'])
            ->get();

        foreach ($products as $product) {
            if (! $imports->contains('product_id', $product->id)) {
                Import::create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $product->price,
                    'total' => $product->price,
                ]);
            }
        }

        return response()->json($this->currentImportPayload());
    }

    private function stagingImportQuery()
    {
        return Import::query()
            ->with('product')
            ->whereHas('product', function ($query) {
                $query->whereIn('user_id', $this->inventoryOwnerIds());
            })
            ->orderBy('id');
    }

    private function currentImportPayload(): array
    {
        $imports = $this->stagingImportQuery()->get();
        $imports->each(function (Import $import): void {
            $import->setAttribute('total', $this->calculateImportTotal($import));
        });

        return [
            'import' => $imports,
            // Keep the payload correct even if an older staging row has a stale total.
            'total' => (float) $imports->sum('total'),
        ];
    }

    private function calculateImportTotal(Import $import): int
    {
        $quantity = max((int) $import->quantity, 0);
        $price = max((float) $import->price, 0);

        return (int) round($quantity * $price);
    }

    private function clearCurrentImportItems(): void
    {
        Import::query()
            ->where(function ($query) {
                $query->whereDoesntHave('product')
                    ->orWhereHas('product', function ($productQuery) {
                        $productQuery->whereIn('user_id', $this->inventoryOwnerIds());
                    });
            })
            ->delete();
    }

    private function inventoryOwnerIds(): array
    {
        $user = Auth::user();

        return $user ? [(int) $user->ownerId()] : [];
    }

    private function normalizePaymentStatus(mixed $value): ?string
    {
        $values = is_array($value) ? $value : ($value === null ? [] : [$value]);

        return collect($values)
            ->filter(fn ($status): bool => is_string($status) || is_numeric($status))
            ->map(fn ($status): string => trim((string) $status))
            ->intersect(ImportCoupon::paymentStatusValues())
            ->first();
    }
}

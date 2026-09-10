<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Branch;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    public function __construct(
        private readonly BranchContext $branchContext
    ) {}
    public function index(Request $request)
    {
        $user = $request->user();
        $hasBranchBrands = $this->hasBranchOwnership();
        $branches = $hasBranchBrands && $user->isAdministrator()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $branchId = $hasBranchBrands && $user->isAdministrator() && $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : ($hasBranchBrands && $user->branch_id ? (int) $user->branch_id : null);

        if ($hasBranchBrands && $user->isAdministrator() && $branchId !== null) {
            abort_unless(
                Branch::query()->whereKey($branchId)->exists(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($request->ajax()) {
            $searchText = $request->query('s');

            $brands = Brand::query()
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%");
                })
                ->when($hasBranchBrands, fn ($query) => $query->with('branch:id,name'))
                ->latest();

            $this->branchContext->scope($brands, $user, 'brands.branch_id');
            if ($hasBranchBrands && $user->isAdministrator() && $branchId !== null) {
                $brands->where('brands.branch_id', $branchId);
            }

            $brands = $brands->paginate(10)->appends($request->query());
            $html = view('admin.brand.table', compact('brands', 'user', 'hasBranchBrands'))->render();

            return response()->json(['html' => $html]);
        }

        $title = 'Thương hiệu';

        return view('admin.brand.index', compact('title', 'branches', 'branchId', 'hasBranchBrands'));
    }

    public function create(Request $request)
    {
        $title = 'Tạo mới thương hiệu';
        $brand = null;
        $hasBranchBrands = $this->hasBranchOwnership();
        $branches = $hasBranchBrands && $request->user()->isAdministrator()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.brand.form', compact('title', 'brand', 'branches', 'hasBranchBrands'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);
        $uploadedLogo = null;

        if ($this->hasBranchOwnership()) {
            $credentials['branch_id'] = $this->branchContext->resolveWriteBranch(
                $request->user(),
                $request->user()->isAdministrator() ? (int) $request->input('branch_id') : null
            );
        }

        return transaction(function () use ($request, $credentials, &$uploadedLogo) {
            if ($request->hasFile('logo')) {
                $uploadedLogo = $request->file('logo')->store('brands', 'public');
                $credentials['logo'] = $uploadedLogo;
            }

            $brand = Brand::create($credentials);

            return successResponse('Tạo mới thương hiệu thành công.', $brand, Response::HTTP_CREATED);
        }, function () use (&$uploadedLogo) {
            deleteImage($uploadedLogo);
        });
    }

    public function edit(Request $request, string $id)
    {
        $brand = $this->visibleBrandQuery($request)->findOrFail($id);
        $title = "Cập nhật thương hiệu - {$brand->name}";

        $hasBranchBrands = $this->hasBranchOwnership();
        $branches = $hasBranchBrands && $request->user()->isAdministrator()
            ? Branch::query()->whereKey($brand->branch_id)->get(['id', 'name'])
            : collect();

        return view('admin.brand.form', compact('title', 'brand', 'branches', 'hasBranchBrands'));
    }

    public function update(Request $request, string $id)
    {
        $brand = $this->visibleBrandQuery($request)->findOrFail($id);
        $credentials = $this->validateRequest($request, $brand);
        unset($credentials['branch_id']);

        return transaction(function () use ($brand, $credentials, $request) {

            $oldLogo = $brand->logo;

            if ($request->hasFile('logo')) {
                $credentials['logo'] = $request->file('logo')->store('brands', 'public');
            }

            $updated = $brand->update($credentials);

            if ($updated && $request->hasFile('logo')) {
                deleteImage($oldLogo);
            }

            return successResponse('Cập nhật thương hiệu thành công.', $brand->fresh(), Response::HTTP_OK);
        });
    }

    public function delete(Request $request, string $id)
    {
        $brand = $this->visibleBrandQuery($request)->findOrFail($id);
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa thương hiệu thành công!',
        ]);
    }

    private function validateRequest(Request $request, ?Brand $brand = null): array
    {
        $branchId = $brand?->branch_id;
        if ($branchId === null && $this->hasBranchOwnership()) {
            $branchId = $request->user()->isAdministrator()
                ? $request->input('branch_id')
                : $request->user()->branch_id;
        }

        $uniqueName = Rule::unique('brands', 'name')->ignore($brand?->id);
        if ($this->hasBranchOwnership()) {
            $uniqueName->where(fn ($query) => $branchId
                ? $query->where('branch_id', (int) $branchId)
                : $query->whereRaw('1 = 0'));
        }

        $rules = [
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'status' => ['required', Rule::in([1, 0, '1', '0'])],
        ];

        if ($this->hasBranchOwnership()) {
            $rules['branch_id'] = $request->user()->isAdministrator() && ! $brand
                ? ['required', 'integer', 'exists:branches,id']
                : ['nullable'];
        }

        return $this->validate($request, $rules, __('request.messages'), [
            'name' => 'Tên thương hiệu',
            'description' => 'Mô tả',
            'logo' => 'Logo',
            'status' => 'Trạng thái',
            'branch_id' => 'Cửa hàng',
        ]);
    }

    private function visibleBrandQuery(Request $request)
    {
        $query = Brand::query();
        $this->branchContext->scope($query, $request->user(), 'brands.branch_id');

        return $query;
    }

    private function hasBranchOwnership(): bool
    {
        return Schema::hasColumn('brands', 'branch_id') && Schema::hasTable('branches');
    }
}

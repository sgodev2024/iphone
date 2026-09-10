<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Branch;
use App\Models\Categories;
use App\Services\CategoryService;
use App\Support\BranchContext;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CategorieController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
        private readonly BranchContext $branchContext
    ) {}

    public function index(Request $request)
    {
        $title = 'Danh mục';
        $user = $request->user();
        $hasBranchCatalog = Schema::hasColumn('categories', 'branch_id');
        $branches = $user->isAdministrator() && Schema::hasTable('branches')
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $branchId = $hasBranchCatalog && $user->isAdministrator() && $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : ($user->branch_id ? (int) $user->branch_id : null);

        if ($request->ajax()) {
            $searchTerm = $request->query('s');
            $categories = Categories::query()
                ->when($hasBranchCatalog, fn ($query) => $query->with('branch:id,name'))
                ->when($searchTerm, fn ($query, $term) => $query->where('name', 'like', '%'.$term.'%'))
                ->latest();

            $this->branchContext->scope($categories, $user, 'categories.branch_id');
            if ($hasBranchCatalog && $user->isAdministrator() && $branchId !== null) {
                abort_unless(Branch::query()->whereKey($branchId)->exists(), Response::HTTP_UNPROCESSABLE_ENTITY);
                $categories->where('categories.branch_id', $branchId);
            }

            $categories = $categories->paginate(10)->appends($request->query());
            $html = view('admin.category.table', compact('categories', 'user', 'hasBranchCatalog'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.category.index', compact('title', 'branches', 'branchId', 'hasBranchCatalog'));
    }

    public function store(CategoryRequest $request)
    {
        return transaction(function () use ($request) {
            $data = $request->validated();

            if (Schema::hasColumn('categories', 'branch_id')) {
                $data['branch_id'] = $this->branchContext->resolveWriteBranch(
                    $request->user(),
                    $request->user()->isAdministrator() ? (int) $request->input('branch_id') : null
                );
            }

            $category = Categories::create($data);

            return successResponse('Thêm mới danh mục thành công', $category, Response::HTTP_CREATED);
        });
    }

    public function destroy(Request $request, $id)
    {
        $query = Categories::query();
        $this->branchContext->scope($query, $request->user(), 'categories.branch_id');
        $category = $query->findOrFail($id);

        try {
            $category->delete();

            $categoriesQuery = Categories::query()->orderByDesc('created_at');
            $this->branchContext->scope($categoriesQuery, $request->user(), 'categories.branch_id');
            $categories = $categoriesQuery->paginate(10);
            $user = $request->user();
            $hasBranchCatalog = Schema::hasColumn('categories', 'branch_id');
            $view = view('admin.category.table', compact('categories', 'user', 'hasBranchCatalog'))->render();

            return response()->json([
                'success' => true,
                'message' => 'Xoá danh mục thành công!',
                'table' => $view,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete category: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục đang có sản phẩm.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function delete(Request $request, $id)
    {
        return $this->destroy($request, $id);
    }

    public function show(Request $request, $id)
    {
        $query = Categories::query();
        $this->branchContext->scope($query, $request->user(), 'categories.branch_id');
        $category = $query->findOrFail($id);

        return successResponse(data: $category);
    }

    public function update($id, CategoryRequest $request)
    {
        $query = Categories::query();
        $this->branchContext->scope($query, $request->user(), 'categories.branch_id');
        $category = $query->findOrFail($id);
        $data = $request->validated();
        unset($data['branch_id']);
        $category->update($data);

        return successResponse('Cập nhật danh mục thành công', $category->fresh(), Response::HTTP_OK);
    }
}

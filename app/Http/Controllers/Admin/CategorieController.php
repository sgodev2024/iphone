<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Categories;
use App\Services\CategoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CategorieController extends Controller
{
    //
    protected $categoryService;
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $title = 'Danh mục';

        if ($request->ajax()) {
            $searchTerm = $request->query('s');

            $categories = Categories::query()
                ->where('user_id', Auth::id())
                ->when($searchTerm, function ($query, $searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->latest()
                ->paginate(10);

            $html = view('admin.category.table', compact('categories'))->render();
            return response()->json(['html' => $html]);
        }

        return view('admin.category.index', compact('title'));
    }

    public function store(CategoryRequest $request)
    {
        return transaction(function () use ($request) {
            $credentials = $request->validated();

            $credentials['user_id'] = Auth::id();

            Categories::create($credentials);

            return successResponse("Thêm mới danh mục thành công");
        });
    }
    public function delete($id)
    {
        try {
            $this->categoryService->deleteCategory($id);

            $category = Categories::orderByDesc('created_at')->paginate(10);
            $view = view('admin.category.table', compact('category'))->render();

            return response()->json(['success' => true, 'message' => 'Xoá danh mục thành công!', 'table' => $view]);
        } catch (Exception $e) {
            Log::error('Failed to delete category: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể xóa danh mục']);
        }
    }

    public function show($id)
    {

        if (!$category = Categories::find($id))  return errorResponse('Không tìm thấy danh mục này trên hệ thông!', 404);

        return successResponse(data: $category);
    }

    public function update($id, CategoryRequest $request)
    {
        if (!$category = Categories::find($id))  return errorResponse('Không tìm thấy danh mục này trên hệ thông!', 404);

        $category->update($request->validated());

        return successResponse('Cập nhật danh mục thành công');
    }
}

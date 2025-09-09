<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Storage;
use App\Services\StorageService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    protected $storageService;
    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $searchText = $request->query('s');

            $storages = Storage::query()
                ->where('user_id', Auth::id())
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%");
                })
                ->latest()
                ->paginate(10);

            $html = view('admin.storage.table', compact('storages'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.storage.index');
    }

    public function show($id)
    {
        $storage = Storage::query()
            ->where('user_id', Auth::id())
            ->find($id);

        if (!$storage) return errorResponse('Không tin thấy kho tên hệ thống!', Response::HTTP_NOT_FOUND);

        return successResponse(data: $storage, isToastr: false);
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);

        return transaction(function () use ($credentials) {
            $credentials['user_id'] = Auth::id();
            Storage::create($credentials);

            return successResponse('Tạo mới kho thành công.', code: Response::HTTP_CREATED);
        });
    }
    public function update(Request $request, string $id)
    {
        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($credentials, $id) {

            $userId = Auth::id();

            if (!$storage = Storage::query()->where('user_id', $userId)->find($id)) return errorResponse('Không tìm thấy kho trên hệ thống!', Response::HTTP_NOT_FOUND);

            $storage->update($credentials);

            return successResponse('Cập nhật kho thành công.', code: Response::HTTP_OK);
        });
    }

    public function detail($id)
    {
        try {
            $storage = $this->storageService->getStorageById($id);
            $product = $this->storageService->getProductInStorage($id);
            return view('admin.storage.detail', compact('product', 'storage'));
        } catch (Exception $e) {
            Log::error('Failed to find Storage info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch Storage info'], 500);
        }
    }

    private function validateRequest($request, $id = null)
    {
        return $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('storages') // thay bằng tên bảng thực tế
                    ->where(fn($q) => $q->where('user_id', Auth::id()))
                    ->ignore($id),
            ],
            'location' => 'nullable|max:255',
        ], __('request.messages'), [
            'name' => 'Tên kho',
            'location' => 'Địa chỉ',
        ]);
    }
}

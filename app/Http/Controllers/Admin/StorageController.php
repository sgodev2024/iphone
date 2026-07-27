<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Storage;
use App\Services\StorageService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        $title = 'Kho hàng';

        if ($request->ajax()) {
            $searchText = trim((string) $request->query('s'));

            $storages = $this->storageQuery()
                ->when($searchText !== '', function (Builder $query) use ($searchText) {
                    $query->where(function (Builder $query) use ($searchText) {
                        $query->where('name', 'like', "%{$searchText}%")
                            ->orWhere('location', 'like', "%{$searchText}%");

                        if (ctype_digit($searchText)) {
                            $query->orWhere('id', (int) $searchText);
                        }
                    });
                })
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $html = view('admin.storage.table', compact('storages'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.storage.index', compact('title'));
    }

    public function show($id)
    {
        $storage = $this->storageQuery()->find($id);

        if (!$storage) return errorResponse('Không tìm thấy kho trên hệ thống!', Response::HTTP_NOT_FOUND);

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

            if (!$storage = $this->storageQuery()->find($id)) return errorResponse('Không tìm thấy kho trên hệ thống!', Response::HTTP_NOT_FOUND);

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
                Rule::unique('storages')
                    ->where(fn($q) => $this->applyStorageScope($q))
                    ->ignore($id),
            ],
            'location' => 'nullable|max:255',
        ], __('request.messages'), [
            'name' => 'Tên kho',
            'location' => 'Địa chỉ',
        ]);
    }

    private function storageQuery(): Builder
    {
        return $this->applyStorageScope(Storage::query());
    }

    private function applyStorageScope($query)
    {
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $ownerIds = collect([$user->id, $user->manager_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $query->where(function ($query) use ($ownerIds, $user) {
            if (!empty($ownerIds)) {
                $query->whereIn('user_id', $ownerIds);
            }

            if ($user->storage_id) {
                $query->orWhere('id', (int) $user->storage_id);
            }
        });
    }
}

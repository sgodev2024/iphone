<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ClientsExport;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientGroupService;
use App\Services\ClientService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    protected $clientService;
    protected $clientGroupService;

    public function __construct(ClientService $clientService, ClientGroupService $clientGroupService)
    {
        $this->clientService = $clientService;
        $this->clientGroupService = $clientGroupService;
    }

    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return view('admin.client.index');
        }

        try {
            $searchText = trim((string) $request->query('s', ''));

            $clients = Client::query()
                // Admin xem toàn bộ khách hàng nên không lọc theo Auth::id()
                ->when($searchText !== '', function ($query) use ($searchText) {
                    $query->where(function ($searchQuery) use ($searchText) {
                        $searchQuery
                            ->where('name', 'like', "%{$searchText}%")
                            ->orWhere('phone', 'like', "%{$searchText}%")
                            ->orWhere('email', 'like', "%{$searchText}%")
                            ->orWhere('address', 'like', "%{$searchText}%")
                            ->orWhere('code', 'like', "%{$searchText}%");
                    });
                })
                ->latest('created_at')
                ->paginate(10)
                ->withQueryString();

            return response()->json([
                'html' => view('admin.client.table', compact('clients'))->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Không thể tải danh sách khách hàng', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Không thể tải danh sách khách hàng.',
            ], 500);
        }
    }
    public function findClient(Request $request)
    {
        $title = 'Khach hang';

        try {
            $client = $this->clientService->findClientByPhone($request->phone);
            $clients = new LengthAwarePaginator(
                $client ? [$client] : [],
                $client ? 1 : 0,
                10,
                1,
                ['path' => Paginator::resolveCurrentPath()]
            );

            return view('admin.client.index', compact('clients', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to find client: ' . $e->getMessage());

            return response()->json(['error' => 'Failed to find client'], 500);
        }
    }

    public function edit($id)
    {
        $title = 'Sửa thông tin khách hàng';
        $clientgroups = $this->clientGroupService->getAllClientGroup();

        $client = Client::query()->findOrFail($id);

        return view(
            'admin.client.edit',
            compact('client', 'title', 'clientgroups')
        );
    }
    public function update($id, Request $request)
    {
        $client = Client::query()->findOrFail($id);
        $credentials = $this->validateClient($request, $id);

        try {
            $client->update($credentials);

            return redirect()
                ->route('admin.client.index')
                ->with('success', 'Cập nhật thông tin khách hàng thành công!');
        } catch (\Throwable $e) {
            Log::error('Không thể cập nhật khách hàng', [
                'client_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'error' => 'Không thể cập nhật khách hàng.',
                ]);
        }
    }

    public function delete($id)
    {
        try {
            $client = Client::query()->findOrFail($id);

            // Giữ Service nếu trong đó có xử lý nghiệp vụ liên quan
            $this->clientService->deleteClient($client->id);

            $clients = Client::query()
                ->latest('created_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Xóa khách hàng thành công!',
                'table' => view(
                    'admin.client.table',
                    compact('clients')
                )->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Không thể xóa khách hàng', [
                'client_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Khách hàng không thể xóa.',
            ], 422);
        }
    }
    public function clientgroup()
    {
        try {
            $title = 'Nhom khach hang';
            $clientgroup = $this->clientGroupService->getAllClientGroup();

            return view('admin.client.group.index', compact('clientgroup', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to list clientgroup: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Khong co loai khach hang.']);
        }
    }

    public function export(Request $request)
    {
        $searchText = trim((string) $request->query('s', ''));

        return Excel::download(
            new ClientsExport($searchText),
            'danh_sach_khach_hang.xlsx'
        );
    }

    private function validateClient(Request $request, $id): array
    {
        return $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'phone' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('clients', 'phone')->ignore($id),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('clients', 'email')->ignore($id),
                ],
                'gender' => ['nullable', 'in:Male,Female'],
                'dob' => ['nullable', 'date'],
                'address' => ['nullable', 'string', 'max:255'],
                'zip_code' => ['nullable', 'string', 'max:20'],
                'clientgroup_id' => ['nullable', 'integer', 'exists:client_group,id'],
            ],
            __('request.messages'),
            [
                'name' => 'Ten khach hang',
                'phone' => 'So dien thoai',
                'email' => 'Email',
                'gender' => 'Gioi tinh',
                'dob' => 'Ngay sinh',
                'address' => 'Dia chi',
                'zip_code' => 'Ma buu dien',
                'clientgroup_id' => 'Nhom khach hang',
            ]
        );
    }
}

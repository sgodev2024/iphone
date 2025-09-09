<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ClientsExport;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Services\ClientGroupService;
use App\Services\ClientService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        if ($request->ajax()) {
            $searchText = trim($request->query('s'));

            $clients = Client::query()
                ->where('user_id', Auth::id())
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    if (is_numeric($searchText)) {
                        // Nếu nhập số -> ưu tiên tìm phone
                        $query->where('phone', 'like', "%{$searchText}%");
                    } elseif (str_contains($searchText, '@')) {
                        // Nếu có @ -> ưu tiên email
                        $query->where('email', 'like', "%{$searchText}%");
                    } else {
                        // Mặc định -> tìm trong name
                        $query->where('name', 'like', "%{$searchText}%");
                    }
                })
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $html = view('admin.client.table', compact('clients'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.client.index');
    }



    public function findClient(Request $request)
    {
        $title = 'Khách hàng';
        try {
            $client = $this->clientService->findClientByPhone($request->phone);

            // Convert single client to a paginator instance
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
        try {
            $clientgroups = $this->clientGroupService->getAllClientGroup();
            $client =  $this->clientService->getClientByID($id);
            return view('admin.client.edit', compact('client', 'title', 'clientgroups'));
        } catch (Exception $e) {
            Log::error('Failed to find client profile');
        }
    }

    public function update($id, Request $request)
    {
        try {
            $client = $this->clientService->updateClient($id, $request->all());
            session()->flash('success', 'Cập nhật thông tin khách hàng thành công!');
            return redirect()->route('admin.client.index');
        } catch (\Exception $e) {
            Log::error('Failed to update client profile: ' . $e->getMessage());
            return ApiResponse::error('Failed to update client profile', 500);
        }
    }

    public function delete($id)
    {
        try {
            $this->clientService->deleteClient($id);
            $clients = Client::orderByDesc('created_at')->paginate(10);
            $view = view('admin.client.table', compact('clients'))->render();
            return response()->json(['success' => true, 'message' => 'Xóa thành công!', 'table' => $view]);
        } catch (Exception $e) {
            Log::error('Failed to delete client: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Khách hàng không thể xóa.']);
        }
    }

    public function clientgroup()
    {
        try {
            $title = 'Nhóm khách hàng';
            $clientgroup = $this->clientGroupService->getAllClientGroup();
            // dd($clientgroup);
            return view('admin.client.group.index', compact('clientgroup', 'title'));
        } catch (Exception $e) {
            Log::error('Failed to list clientgroup: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'không có loại khách hàng.']);
        }
    }

    public function export()
    {
        return Excel::download(new ClientsExport, 'clients.xlsx');
    }
}

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
        if ($request->ajax()) {
            $searchText = trim((string) $request->query('s'));

            $clients = Client::query()
                ->where('user_id', Auth::id())
                ->when(! empty($searchText), function ($query) use ($searchText) {
                    if (is_numeric($searchText)) {
                        $query->where('phone', 'like', "%{$searchText}%");
                    } elseif (str_contains($searchText, '@')) {
                        $query->where('email', 'like', "%{$searchText}%");
                    } else {
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
        $title = 'Sua thong tin khach hang';
        $clientgroups = $this->clientGroupService->getAllClientGroup();
        $client = Client::query()->where('user_id', Auth::id())->findOrFail($id);

        return view('admin.client.edit', compact('client', 'title', 'clientgroups'));
    }

    public function update($id, Request $request)
    {
        $client = Client::query()->where('user_id', Auth::id())->findOrFail($id);
        $credentials = $this->validateClient($request, $id);

        try {
            $client->update($credentials);
        } catch (Exception $e) {
            Log::error('Failed to update client profile: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Khong the cap nhat khach hang. Chi tiet loi da duoc ghi vao log.']);
        }

        session()->flash('success', 'Cap nhat thong tin khach hang thanh cong!');

        return redirect()->route('admin.client.index');
    }

    public function delete($id)
    {
        try {
            $this->clientService->deleteClient($id);
            $clients = Client::query()
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->paginate(10);
            $view = view('admin.client.table', compact('clients'))->render();

            return response()->json(['success' => true, 'message' => 'Xoa thanh cong!', 'table' => $view]);
        } catch (Exception $e) {
            Log::error('Failed to delete client: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Khach hang khong the xoa.']);
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

    public function export()
    {
        return Excel::download(new ClientsExport, 'clients.xlsx');
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
                    Rule::unique('clients', 'phone')
                        ->where(fn ($query) => $query->where('user_id', Auth::id()))
                        ->ignore($id),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('clients', 'email')
                        ->where(fn ($query) => $query->where('user_id', Auth::id()))
                        ->ignore($id),
                ],
                'gender' => ['required', 'in:Male,Female'],
                'dob' => ['required', 'date'],
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

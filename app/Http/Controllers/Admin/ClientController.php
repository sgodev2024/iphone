<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ClientsExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Services\ClientGroupService;
use App\Services\ClientService;
use App\Support\BranchContext;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService,
        private ClientGroupService $clientGroupService,
        private BranchContext $branchContext,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $request->ajax()) {
            $branches = $user->isAdministrator()
                ? Branch::query()->orderBy('name')->get(['id', 'name'])
                : collect();

            return view('admin.client.index', compact('branches'));
        }

        $clients = $this->filteredClientQuery($request)
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'html' => view('admin.client.table', [
                'clients' => $clients,
                'showBranchColumn' => $user->isAdministrator(),
            ])->render(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.client.create', [
            'branches' => $request->user()->isAdministrator()
                ? Branch::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'title' => 'Thêm khách hàng',
        ]);
    }

    public function store(Request $request)
    {
        [$credentials, $branchId] = $this->validatedClient($request);
        $credentials['user_id'] = $request->user()->ownerId();
        $credentials['branch_id'] = $branchId;

        $client = DB::transaction(fn () => Client::query()->create($credentials));

        return redirect()
            ->route('admin.client.show', $client)
            ->with('success', 'Thêm khách hàng thành công!');
    }

    public function update(Request $request, int $client)
    {
        $customer = $this->clientQuery($request->user())->findOrFail($client);
        [$credentials] = $this->validatedClient($request, $customer);

        DB::transaction(fn () => $customer->update($credentials));

        return redirect()
            ->route('admin.client.show', $customer)
            ->with('success', 'Cập nhật thông tin khách hàng thành công!');
    }

    public function destroy(Request $request, int $client)
    {
        $customer = $this->clientQuery($request->user())->findOrFail($client);
        $branchId = $request->user()->isAdministrator()
            ? null
            : $this->branchContext->branchId($request->user());

        try {
            $this->clientService->deleteClients([$customer->id], $branchId);
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return back()->withErrors(['delete' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ngừng hoạt động khách hàng thành công!',
            ]);
        }

        return redirect()
            ->route('admin.client.index')
            ->with('success', 'Ngừng hoạt động khách hàng thành công!');
    }

    public function show(Request $request, int $client)
    {
        $customer = $this->clientQuery($request->user())
            ->with('branch')
            ->findOrFail($client);

        $orders = $this->branchContext
            ->scope(Order::query(), $request->user())
            ->where('client_id', $customer->id)
            ->latest('created_at')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        return view('admin.client.show', [
            'client' => $customer,
            'orders' => $orders,
            'title' => 'Chi tiết khách hàng',
        ]);
    }

    public function edit(Request $request, int $client)
    {
        $customer = $this->clientQuery($request->user())
            ->with('branch')
            ->findOrFail($client);

        return view('admin.client.edit', [
            'client' => $customer,
            'title' => 'Sửa thông tin khách hàng',
        ]);
    }

    public function clientgroup()
    {
        $clientgroup = $this->clientGroupService->getAllClientGroup();
        $title = 'Nhóm khách hàng';

        return view('admin.client.group.index', compact('clientgroup', 'title'));
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $branchId = $user->isAdministrator()
            ? $this->validatedFilterBranch($request)
            : $this->branchContext->branchId($user);

        return Excel::download(
            new ClientsExport(trim((string) $request->query('s', '')), $branchId),
            'danh_sach_khach_hang.xlsx'
        );
    }

    private function filteredClientQuery(Request $request): Builder
    {
        $searchText = trim((string) $request->query('s', ''));
        $branchId = $request->user()->isAdministrator()
            ? $this->validatedFilterBranch($request)
            : null;

        return $this->clientQuery($request->user())
            ->with('branch')
            ->when($branchId !== null, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when($searchText !== '', function (Builder $query) use ($searchText): void {
                $query->where(function (Builder $searchQuery) use ($searchText): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$searchText.'%')
                        ->orWhere('phone', 'like', '%'.$searchText.'%')
                        ->orWhere('email', 'like', '%'.$searchText.'%');
                });
            });
    }

    private function clientQuery(User $user): Builder
    {
        return $this->branchContext->scope(Client::query(), $user);
    }

    /** @return array{0: array<string, mixed>, 1: int|null} */
    private function validatedClient(Request $request, ?Client $client = null): array
    {
        $user = $request->user();
        $creating = $client === null;
        $branchRule = $creating && $user->isAdministrator()
            ? ['required', 'integer', Rule::exists('branches', 'id')]
            : ['prohibited'];

        $request->validate(['branch_id' => $branchRule]);

        $branchId = $creating
            ? $this->branchContext->resolveWriteBranch(
                $user,
                $user->isAdministrator() ? (int) $request->input('branch_id') : null
            )
            : ($client->branch_id === null ? null : (int) $client->branch_id);

        $uniquePhone = Rule::unique('clients', 'phone')
            ->where(fn ($query) => $branchId === null
                ? $query->whereNull('branch_id')
                : $query->where('branch_id', $branchId))
            ->whereNull('deleted_at');

        if ($client !== null) {
            $uniquePhone->ignore($client->id);
        }

        $credentials = $request->validate(
            [
                'branch_id' => $branchRule,
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20', $uniquePhone],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
            ],
            __('request.messages'),
            [
                'branch_id' => 'Cửa hàng',
                'name' => 'Tên khách hàng',
                'phone' => 'Số điện thoại',
                'email' => 'Email',
                'address' => 'Địa chỉ',
            ]
        );

        unset($credentials['branch_id']);

        return [$credentials, $branchId];
    }

    private function validatedFilterBranch(Request $request): ?int
    {
        if (! $request->filled('branch_id')) {
            return null;
        }

        $validated = $request->validate([
            'branch_id' => ['integer', Rule::exists('branches', 'id')],
        ]);

        return (int) $validated['branch_id'];
    }
}

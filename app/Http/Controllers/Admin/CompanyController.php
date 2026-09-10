<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyRequest;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $searchText = $request->query('s');

            $companies = $this->companyQuery($request->user())
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%");
                })
                ->latest()
                ->paginate(10);

            $html = view('admin.company.table', compact('companies'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.company.index');
    }

    public function create()
    {
        $banks = Bank::query()->pluck('name', 'id')->toArray();
        $cities = City::query()->pluck('name', 'id')->toArray();
        $title = 'Tạo mới nhà cung cấp';
        $company = null;
        $branches = Auth::user()->isAdministrator()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $canChangeBranch = true;

        return view('admin.company.form', compact(
            'banks',
            'cities',
            'title',
            'company',
            'branches',
            'canChangeBranch'
        ));
    }

    public function store(CompanyRequest $request)
    {
        return transaction(function () use ($request) {
            $credentials = $request->validated();

            $credentials['user_id'] = $request->user()->ownerId();
            $credentials['branch_id'] = $this->branchContext->resolveWriteBranch(
                $request->user(),
                $request->user()->isAdministrator() ? (int) $credentials['branch_id'] : null
            );

            Company::create($credentials);

            return successResponse("Tạo mới nhà cung cấp thành công.", code: 201);
        });
    }

    public function edit(string $id)
    {
        $company = $this->companyQuery(Auth::user())->findOrFail($id);
        $banks = Bank::query()->pluck('name', 'id')->toArray();
        $cities = City::query()->pluck('name', 'id')->toArray();
        $title = "Chỉnh sửa nhà cung cấp - {$company->name}";
        $branches = Auth::user()->isAdministrator()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $canChangeBranch = ! $company->hasBranchHistory();

        return view('admin.company.form', compact(
            'banks',
            'cities',
            'title',
            'company',
            'branches',
            'canChangeBranch'
        ));
    }

    public function update(string $id, CompanyRequest $request)
    {
        $company = $this->companyQuery($request->user())->findOrFail($id);

        return DB::transaction(function () use ($request, $company) {
            $credentials = $request->validated();

            $credentials['branch_id'] = $this->branchContext->resolveWriteBranch(
                $request->user(),
                $request->user()->isAdministrator() ? (int) $credentials['branch_id'] : null
            );

            $company->update($credentials);

            return successResponse("Cập nhật nhà cung cấp thành công.");
        });
    }

    public function show(Request $request, string $id)
    {
        return successResponse(data: $this->companyQuery($request->user())->findOrFail($id));
    }

    public function destroy(Request $request, string $id)
    {
        $company = $this->companyQuery($request->user())->findOrFail($id);

        return transaction(function () use ($company) {
            $company->delete();

            return successResponse('Xóa nhà cung cấp thành công.');
        });
    }

    private function companyQuery(User $user): Builder
    {
        $query = Company::query()->branchOwned();

        return $this->branchContext->scope($query, $user);
    }
}

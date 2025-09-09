<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Bank;
use App\Models\City;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\CompanyService;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    protected $companyService;
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $searchText = $request->query('s');

            $companies = Company::query()
                ->where('user_id', Auth::id())
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
        return view('admin.company.form', compact('banks', 'cities', 'title', 'company'));
    }

    public function store(CompanyRequest $request)
    {
        return transaction(function () use ($request) {
            $credentials = $request->validated();

            $credentials['user_id'] = Auth::id();

            Company::create($credentials);

            return successResponse("Tạo mới nhà cung cấp thành công.", code: 201);
        });
    }

    public function edit(string $id)
    {
        $company = Company::findOrFail($id);
        $banks = Bank::query()->pluck('name', 'id')->toArray();
        $cities = City::query()->pluck('name', 'id')->toArray();
        $title = "Chỉnh sửa nhà cung cấp - {$company->name}";
        return view('admin.company.form', compact('banks', 'cities', 'title', 'company'));
    }

    public function update(string $id, CompanyRequest $request)
    {
        if (!$company = Company::findOrFail($id)) return errorResponse("Không tìm thấy nhà cung cấp.", 404);

        return transaction(function () use ($request, $company) {
            $credentials = $request->validated();

            $company->update($credentials);

            return successResponse("Cập nhật nhà cung cấp thành công.");
        });
    }
}

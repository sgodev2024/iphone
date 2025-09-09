<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends Controller
{
    public function index(Request $request)
    {

        $searchText = $request->input('s');

        $branchs = Branch::query()
            ->when(!empty($searchText), function (Builder $query) use ($searchText) {
                $query->where('name', 'like', "%$searchText%");
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());


        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.branch.table', compact('branchs'))->render()
            ], Response::HTTP_OK);
        }

        return view('admin.branch.index', compact('branchs'));
    }

    public function show(string $id)
    {
        if (!$branch = Branch::query()->where('user_id', Auth::id())->find($id)) {
            return response()->json([
                'message' => 'Dữ liệu không tồn tại trên hệ thống!'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $branch
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name'          => 'required|string|max:255',
                'manager_name'  => 'nullable|string|max:255',
                'address'       => 'required|string|max:500',
                'phone'         => 'nullable|string|regex:/^0[0-9]{9}$/',
                'email'         => 'nullable|email|max:255',
                'status'        => 'required|in:0,1',
            ],
            __('request.messages'),
            [
                'name'         => 'Tên chi nhánh',
                'manager_name' => 'Tên người quản lý',
                'address'      => 'Địa chỉ',
                'phone'        => 'Số điện thoại',
                'email'        => 'Email',
                'status'       => 'Trạng thái',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $validator->validate();

        $credentials['user_id'] = Auth::id();

        Branch::create($credentials);

        return response()->json([
            'message' => 'Tạo chi nhánh thành công.'
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('branches', 'name')
                        ->where(fn($query) => $query->where('user_id', Auth::id()))
                        ->ignore($id), // bỏ qua chính record đang update
                ],
                'manager_name' => 'nullable|string|max:255',
                'address'      => 'required|string|max:500',
                'phone'        => 'nullable|string|regex:/^0[0-9]{9}$/',
                'email'        => 'nullable|email|max:255',
                'status'       => 'required|in:0,1',
            ],
            __('request.messages'),
            [
                'name'         => 'Tên chi nhánh',
                'manager_name' => 'Tên người quản lý',
                'address'      => 'Địa chỉ',
                'phone'        => 'Số điện thoại',
                'email'        => 'Email',
                'status'       => 'Trạng thái',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $validator->validate();

        $credentials['user_id'] = Auth::id();

        $branch = Branch::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$branch) {
            return response()->json([
                'message' => 'Chi nhánh không tồn tại hoặc bạn không có quyền chỉnh sửa.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Update
        $branch->update($credentials);

        return response()->json([
            'message' => 'Cập nhật chi nhánh thành công.'
        ], Response::HTTP_OK);
    }


    public function destroy(Request $request)
    {
        $data = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:branches,id',
        ]);

        if ($data->fails()) {
            return response()->json([
                'message' => $data->errors()->first()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ids = $data->validate()['ids'];

        Branch::query()->whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Xóa chi nhánh thành công.',
        ], Response::HTTP_OK);
    }

    public function changeStatus(Request $request)
    {
        $data = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:branches,id',
        ]);

        if ($data->fails()) {
            return response()->json([
                'message' => $data->errors()->first()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ids = $data->validate()['ids'];

        Branch::query()->whereIn('id', $ids)
            ->each(function ($branch) {
                $branch->update(['status' => !$branch->status]);
            });

        return response()->json([
            'message' => 'Thay đổi trạng thái thành công.',
        ], Response::HTTP_OK);
    }
}

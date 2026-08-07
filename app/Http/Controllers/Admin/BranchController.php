<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $searchText = $request->input('s');

        $branchs = Branch::query()
            ->where('user_id', Auth::id())
            ->when(! empty($searchText), function (Builder $query) use ($searchText) {
                $query->where('name', 'like', "%{$searchText}%");
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.branch.table', compact('branchs'))->render(),
            ], Response::HTTP_OK);
        }

        return view('admin.branch.index', compact('branchs'));
    }

    public function create(Request $request)
    {
        return $this->index($request);
    }

    public function show(string $id)
    {
        $branch = Branch::query()
            ->where('user_id', Auth::id())
            ->find($id);

        if (! $branch) {
            return response()->json([
                'message' => 'Du lieu khong ton tai tren he thong.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $branch,
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $credentials = $this->validateBranch($request);
        $credentials['user_id'] = Auth::id();

        Branch::create($credentials);

        return response()->json([
            'message' => 'Tao chi nhanh thanh cong.',
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::query()
            ->where('user_id', Auth::id())
            ->find($id);

        if (! $branch) {
            return response()->json([
                'message' => 'Chi nhanh khong ton tai hoac ban khong co quyen chinh sua.',
            ], Response::HTTP_NOT_FOUND);
        }

        $credentials = $this->validateBranch($request, $id);

        $branch->update($credentials);

        return response()->json([
            'message' => 'Cap nhat chi nhanh thanh cong.',
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
        ]);

        Branch::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'message' => 'Xoa chi nhanh thanh cong.',
        ], Response::HTTP_OK);
    }

    public function changeStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
        ]);

        Branch::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['ids'])
            ->each(function (Branch $branch) {
                $branch->update(['status' => ! $branch->status]);
            });

        return response()->json([
            'message' => 'Thay doi trang thai thanh cong.',
        ], Response::HTTP_OK);
    }

    private function validateBranch(Request $request, $id = null): array
    {
        return $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('branches', 'name')
                        ->where(fn ($query) => $query->where('user_id', Auth::id()))
                        ->ignore($id),
                ],
                'manager_name' => ['nullable', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/'],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => ['required', 'in:0,1'],
            ],
            __('request.messages'),
            [
                'name' => 'Ten chi nhanh',
                'manager_name' => 'Ten nguoi quan ly',
                'address' => 'Dia chi',
                'phone' => 'So dien thoai',
                'email' => 'Email',
                'status' => 'Trang thai',
            ]
        );
    }
}

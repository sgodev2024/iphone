<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ClientImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Throwable;

class ClientImportController extends Controller
{
    public function __construct(private BranchContext $branchContext) {}

    public function template()
    {
        return Excel::download(new ClientImportTemplateExport(), 'mau-import-khach-hang.xlsx');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $branchRule = $user->isAdministrator()
            ? ['required', 'integer', Rule::exists('branches', 'id')]
            : ['prohibited'];

        $validated = $request->validate([
            'branch_id' => $branchRule,
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $branchId = $this->branchContext->resolveWriteBranch(
            $user,
            $user->isAdministrator() ? (int) $validated['branch_id'] : null
        );

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Không thể đọc file Excel hợp lệ.']);
        }

        if ($spreadsheet->getSheetCount() !== 1) {
            throw ValidationException::withMessages(['file' => 'File mẫu chỉ được có một sheet.']);
        }

        $sheet = $spreadsheet->getActiveSheet();
        if (Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > 4) {
            throw ValidationException::withMessages(['file' => 'File Excel chỉ được có 4 cột: Họ tên, Số điện thoại, Email, Địa chỉ.']);
        }

        $headings = array_map(
            static fn ($value): string => trim((string) ($value ?? '')),
            $sheet->rangeToArray('A1:D1', null, false, true, false)[0]
        );
        if ($headings !== ['Họ tên', 'Số điện thoại', 'Email', 'Địa chỉ']) {
            throw ValidationException::withMessages(['file' => 'Header file Excel phải là: Họ tên, Số điện thoại, Email, Địa chỉ.']);
        }

        $lastRow = $sheet->getHighestDataRow();
        if ($lastRow > 5001) {
            throw ValidationException::withMessages(['file' => 'File Excel chỉ được tối đa 5000 dòng khách hàng.']);
        }

        $rows = [];
        $errors = [];
        $phonesInFile = [];

        for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $values = array_map(
                static fn ($value): string => trim((string) ($value ?? '')),
                $sheet->rangeToArray('A'.$rowNumber.':D'.$rowNumber, null, false, true, false)[0]
            );
            if ($values === ['', '', '', '']) {
                continue;
            }

            [$name, $phone, $email, $address] = $values;
            $data = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email === '' ? null : $email,
                'address' => $address === '' ? null : $address,
            ];
            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'phone' => [
                    'required', 'string', 'max:20',
                    Rule::unique('clients', 'phone')
                        ->where(fn ($query) => $query->where('branch_id', $branchId))
                        ->whereNull('deleted_at'),
                ],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
            ], [
                'name.required' => 'Họ tên không được để trống.',
                'name.max' => 'Họ tên không được vượt quá 255 ký tự.',
                'phone.required' => 'Số điện thoại không hợp lệ.',
                'phone.max' => 'Số điện thoại không hợp lệ.',
                'phone.unique' => 'Số điện thoại đã tồn tại trong cửa hàng.',
                'email.email' => 'Email không đúng định dạng.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
                'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            ]);

            foreach ($validator->errors()->all() as $message) {
                $errors[] = "Dòng {$rowNumber}: {$message}";
            }
            if ($phone !== '' && isset($phonesInFile[$phone])) {
                $errors[] = "Dòng {$rowNumber}: Số điện thoại trùng với dòng {$phonesInFile[$phone]}.";
            }
            if ($phone !== '') {
                $phonesInFile[$phone] ??= $rowNumber;
            }

            if (array_filter($values, static fn (string $value): bool => str_starts_with($value, '='))) {
                $errors[] = "Dòng {$rowNumber}: Không chấp nhận công thức Excel.";
            }

            $rows[] = $data;
        }

        if ($rows === []) {
            $errors[] = 'File Excel không có dòng khách hàng.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        DB::transaction(function () use ($rows, $branchId, $user): void {
            foreach ($rows as $data) {
                Client::query()->create($data + [
                    'user_id' => $user->ownerId(),
                    'branch_id' => $branchId,
                ]);
            }
        });

        return redirect()->route('admin.client.index')
            ->with('success', 'Đã import '.count($rows).' khách hàng.');
    }
}

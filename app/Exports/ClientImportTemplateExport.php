<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class ClientImportTemplateExport extends StringValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    public function headings(): array
    {
        return ['Họ tên', 'Số điện thoại', 'Email', 'Địa chỉ'];
    }

    public function array(): array
    {
        return [['Nguyễn Văn A', '0987654321', 'nguyenvana@example.com', 'Hà Nội']];
    }
}

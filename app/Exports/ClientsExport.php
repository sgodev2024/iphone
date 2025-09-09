<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromCollection, WithHeadings, WithMapping
{
    private $rowNumber = 0;

    public function collection()
    {
        return Client::query()->select('id', 'name', 'phone', 'email', 'address', 'created_at')->get();
    }

    // Mapping lại dữ liệu xuất ra
    public function map($client): array
    {
        return [
            ++$this->rowNumber,
            $client->name,
            $client->phone,
            $client->email,
            $client->address,
            $client->created_at ? $client->created_at->format('d/m/Y') : '',
        ];
    }

    // Tiêu đề cột
    public function headings(): array
    {
        return ['STT', 'Tên KH', 'Số điện thoại', 'Email', 'Địa chỉ', 'Ngày tạo'];
    }
}

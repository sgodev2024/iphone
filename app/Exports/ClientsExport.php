<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromCollection, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly string $searchText = '',
        private readonly ?int $branchId = null,
    ) {}

    public function collection()
    {
        return Client::query()
            ->select([
                'id',
                'name',
                'phone',
                'email',
                'address',
                'created_at',
            ])
            ->when($this->branchId !== null, fn ($query) => $query->where('branch_id', $this->branchId))
            ->when($this->searchText !== '', function ($query) {
                $searchText = $this->searchText;

                $query->where(function ($searchQuery) use ($searchText) {
                    $searchQuery
                        ->where('name', 'like', "%{$searchText}%")
                        ->orWhere('phone', 'like', "%{$searchText}%")
                        ->orWhere('email', 'like', "%{$searchText}%")
                        ->orWhere('address', 'like', "%{$searchText}%")
                        ->orWhere('code', 'like', "%{$searchText}%");
                });
            })
            ->latest('created_at')
            ->get();
    }

    public function map($client): array
    {
        return [
            ++$this->rowNumber,
            $client->name ?? '',
            $client->phone ?? '',
            $client->email ?? '',
            $client->address ?? '',
            $client->created_at?->format('d/m/Y') ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'STT',
            'Tên khách hàng',
            'Số điện thoại',
            'Email',
            'Địa chỉ',
            'Ngày tạo',
        ];
    }
}

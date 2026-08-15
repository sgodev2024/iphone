<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerDebtCollection;
use App\Support\DecimalAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CustomerDebtCollectionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d', 'required_with:to_date'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'required_with:from_date', 'after_or_equal:from_date'],
            'client_id' => ['nullable', 'integer'],
            'customer' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
            'collection_number' => ['nullable', 'string', 'max:32'],
        ]);

        $ownerId = (int) $request->user()->ownerId();
        $customer = trim((string) $request->query('customer'));
        $collectionNumber = trim((string) $request->query('collection_number'));

        $collections = CustomerDebtCollection::query()
            ->where('owner_id', $ownerId)
            ->with([
                'client:id,code,name,phone',
                'creator:id,name',
                'moneyAccount:id,code,name',
            ])
            ->withCount('allocations')
            ->when($request->filled('from_date'), fn ($query) => $query
                ->whereBetween('collection_date', [$request->query('from_date'), $request->query('to_date')]))
            ->when($request->filled('client_id'), fn ($query) => $query
                ->where('client_id', (int) $request->query('client_id')))
            ->when($customer !== '', fn ($query) => $query
                ->whereHas('client', function ($clientQuery) use ($customer): void {
                    $clientQuery->where(function ($search) use ($customer): void {
                        $search->where('name', 'like', "%{$customer}%")
                            ->orWhere('phone', 'like', "%{$customer}%")
                            ->orWhere('code', 'like', "%{$customer}%");
                    });
                }))
            ->when($request->filled('payment_method'), fn ($query) => $query
                ->where('payment_method', $request->query('payment_method')))
            ->when($collectionNumber !== '', fn ($query) => $query
                ->where('collection_number', 'like', "%{$collectionNumber}%"))
            ->orderByDesc('collection_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.debt.customer.collections.index', compact('collections'));
    }

    public function show(Request $request, int $collection)
    {
        $collection = CustomerDebtCollection::query()
            ->where('owner_id', (int) $request->user()->ownerId())
            ->with([
                'client:id,code,name,phone',
                'creator:id,name',
                'moneyAccount:id,code,name',
                'allocations.order:id,user_id,code,total_money',
                'allocations.paymentTransaction:id,user_id,collection_id,transaction_date,description,status',
            ])
            ->withSum('allocations as allocated_total', 'allocated_amount')
            ->findOrFail($collection);

        $allocatedTotal = (string) ($collection->allocated_total ?? '0.00');
        $hasIntegrityMismatch = DecimalAmount::compare(
            (string) $collection->total_amount,
            $allocatedTotal
        ) !== 0;

        return view('admin.debt.customer.collections.show', compact(
            'collection',
            'allocatedTotal',
            'hasIntegrityMismatch'
        ));
    }

    public function attachment(Request $request, int $collection)
    {
        $collection = CustomerDebtCollection::query()
            ->where('owner_id', (int) $request->user()->ownerId())
            ->findOrFail($collection);

        abort_if($collection->attachment === null, Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($collection->attachment), Response::HTTP_NOT_FOUND);

        return $disk->response($collection->attachment);
    }
}

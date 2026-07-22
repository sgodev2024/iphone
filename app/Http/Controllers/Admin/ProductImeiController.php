<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductImeiController extends Controller
{
    public function index(Request $request, Product $product): View
    {
        $this->ensureProductBelongsToCurrentUser($product);

        $search = trim((string) $request->query('search', ''));
        $product->loadCount([
            'imeis as imei_stock_count' => fn ($query) => $query->inStock(),
        ]);

        $imeis = $product->imeis()
            ->with(['importDetail.import.companyRelation'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('imei', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $title = "Quản lý IMEI – {$product->name}";

        return view('admin.product.imeis.index', compact('title', 'product', 'imeis', 'search'));
    }

    private function ensureProductBelongsToCurrentUser(Product $product): void
    {
        abort_unless((int) $product->user_id === (int) Auth::id(), 404);
    }
}

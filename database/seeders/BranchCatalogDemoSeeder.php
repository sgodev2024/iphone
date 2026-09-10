<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Categories;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Roles;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BranchCatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $administrator = User::query()
                ->where('role_id', Roles::ADMINISTRATOR_ID)
                ->orderBy('id')
                ->firstOrFail();

            $storeAdmins = User::query()
                ->where('role_id', Roles::ADMIN_STORE_ID)
                ->orderBy('id')
                ->limit(2)
                ->get();

            while ($storeAdmins->count() < 2) {
                $number = $storeAdmins->count() + 1;
                $storeAdmins->push(User::create([
                    'name' => "Quản lý cửa hàng {$number}",
                    'email' => "store{$number}@example.test",
                    'phone' => '091000000'.$number,
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'role_id' => Roles::ADMIN_STORE_ID,
                ]));
            }

            foreach ($storeAdmins->values() as $index => $storeAdmin) {
                $number = $index + 1;
                $branch = Branch::updateOrCreate(
                    ['user_id' => $administrator->id, 'name' => "Cửa hàng mẫu {$number}"],
                    [
                        'admin_store_user_id' => $storeAdmin->id,
                        'manager_name' => $storeAdmin->name,
                        'address' => "Địa chỉ cửa hàng mẫu {$number}",
                        'phone' => '028000000'.$number,
                        'email' => "branch{$number}@example.test",
                        'status' => true,
                    ]
                );

                $storage = Storage::updateOrCreate(
                    ['branch_id' => $branch->id, 'name' => "Kho cửa hàng {$number}"],
                    ['user_id' => $storeAdmin->id, 'location' => $branch->address]
                );

                $storeAdmin->update([
                    'branch_id' => $branch->id,
                    'storage_id' => $storage->id,
                    'status' => 'active',
                ]);

                $category = Categories::updateOrCreate(
                    ['branch_id' => $branch->id, 'name' => 'Điện thoại'],
                    ['description' => 'Danh mục riêng của cửa hàng', 'status' => true]
                );

                $quantityProduct = Product::updateOrCreate(
                    ['branch_id' => $branch->id, 'code' => 'SP-DEMO'],
                    [
                        'user_id' => $storeAdmin->id,
                        'category_id' => $category->id,
                        'barcode' => '8930000000001',
                        'name' => 'Phụ kiện mẫu',
                        'price' => 100000,
                        'price_buy' => 150000,
                        'product_unit' => 'Chiếc',
                        'quantity' => 10,
                        'inventory_tracking' => Product::INVENTORY_TRACKING_QUANTITY,
                        'description' => 'Sản phẩm quản lý theo số lượng',
                        'status' => 'published',
                    ]
                );

                ProductStorage::updateOrCreate(
                    ['product_id' => $quantityProduct->id, 'storage_id' => $storage->id],
                    ['quantity' => 10]
                );

                $imeiProduct = Product::updateOrCreate(
                    ['branch_id' => $branch->id, 'code' => 'SP-IMEI'],
                    [
                        'user_id' => $storeAdmin->id,
                        'category_id' => $category->id,
                        'barcode' => '8930000000002',
                        'name' => 'iPhone 15',
                        'price' => 10000000,
                        'price_buy' => 12000000,
                        'product_unit' => 'Chiếc',
                        'quantity' => 1,
                        'inventory_tracking' => Product::INVENTORY_TRACKING_IMEI,
                        'description' => 'Sản phẩm quản lý theo IMEI',
                        'status' => 'published',
                    ]
                );

                ProductStorage::updateOrCreate(
                    ['product_id' => $imeiProduct->id, 'storage_id' => $storage->id],
                    ['quantity' => 1]
                );

                ProductImei::withTrashed()->updateOrCreate(
                    ['imei' => '35600000000000'.$number],
                    [
                        'product_id' => $imeiProduct->id,
                        'storage_id' => $storage->id,
                        'barcode' => '290000000000'.$number,
                        'status' => ProductImei::STATUS_IN_STOCK,
                        'deleted_at' => null,
                    ]
                );
            }

            $firstBranch = Branch::query()->orderBy('id')->first();
            $firstStorage = $firstBranch?->storages()->orderBy('id')->first();
            User::query()
                ->where('role_id', Roles::STAFF_ID)
                ->whereNull('branch_id')
                ->update([
                    'manager_id' => $firstBranch?->admin_store_user_id,
                    'branch_id' => $firstBranch?->id,
                    'storage_id' => $firstStorage?->id,
                    'status' => 'active',
                ]);
        });
    }
}

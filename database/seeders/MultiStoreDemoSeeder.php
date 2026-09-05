<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BankVoucher;
use App\Models\CashVoucher;
use App\Models\Client;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounting\CustomerDebtSnapshotService;
use App\Services\Accounting\SupplierDebtSnapshotService;
use App\Services\CustomerDebtCollectionService;
use App\Services\GenericBankVoucherService;
use App\Services\GenericCashVoucherService;
use App\Services\OrderReturnService;
use App\Services\SupplierPaymentService;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Deterministic, local-only multi-store demo dataset.
 *
 * The seeder deliberately does not alter production authorization or business
 * rules.  It only creates source records and invokes the canonical POS/debt/
 * supplier-payment services where those workflows are available.
 */
class MultiStoreDemoSeeder extends Seeder
{
    private const PASSWORD = '123456';
    private const SEED = 20260905;
    private const TODAY = '2026-09-05';

    private $faker;
    private array $users = [];
    private array $branches = [];
    private array $storages = [];
    private array $clients = [];
    private array $companies = [];
    private array $products = [];
    private array $accounts = [];
    private array $orders = [];
    private int $staffSequence = 0;

    public function run(bool $reset = true, bool $includeLegacy = false): array
    {
        Carbon::setTestNow(Carbon::parse(self::TODAY.' 12:00:00'));
        $this->faker = FakerFactory::create('vi_VN');
        $this->faker->seed(self::SEED);

        if ($reset) {
            $this->resetBusinessData();
        }

        $this->ensureRoles();
        $this->ensureAccounts();
        $this->createUsers();
        $this->createBranchesAndStorages();
        $this->createStaff();
        $this->verifyPasswords();
        $this->createCatalog();
        $this->createParties();
        $this->createInventoryAndImeis();
        $this->createImports();
        $this->createOrders();
        $this->createReturns();
        $this->normalizeInventoryTargets();
        $this->createCashAndBank();
        $this->collectCustomerDebt();
        $this->paySupplierDebt();

        if ($includeLegacy) {
            $this->createLegacyRecords();
        }

        $this->rebuildSnapshots();
        $this->assertInvariants();
        $summary = $this->summary($includeLegacy);
        $this->writeGuide($summary, $includeLegacy);

        return $summary;
    }

    private function resetBusinessData(): void
    {
        // This command is explicitly local/dev-only.  Keep roles, permissions,
        // banks, chart of accounts and the application config row intact.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'transaction_entries', 'transactions',
            'customer_debt_collection_allocations', 'customer_debt_collections',
            'customer_debt_snapshot_states', 'customer_debt_yearly_snapshots',
            'supplier_debt_snapshot_states', 'supplier_debt_yearly_snapshots',
            'cash_vouchers', 'bank_vouchers',
            'order_return_details', 'order_returns',
            'order_details', 'orders',
            'import_detail', 'import_coupon', 'import',
            'product_imeis', 'product_storage',
            'supplier_debts_detail', 'supplier_debts', 'suppliers',
            'company_product', 'companies', 'clients',
            'products', 'storages', 'branches',
            'carts', 'check_inventory', 'user_info', 'user_wallet',
            'sgo_transactions',
        ] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        // Retain user id 1 because config.user_id is required; it becomes the
        // first global Administrator.  Remove all prior local test accounts.
        DB::table('users')->where('id', '<>', 1)->delete();
        DB::table('users')->where('id', 1)->update([
            'manager_id' => null,
            'name' => 'Nguyễn Đức Minh',
            'email' => 'administrator1@sgo.test',
            'password' => Hash::make(self::PASSWORD),
            'status' => 'active',
            'role_id' => 1,
            'branch_id' => null,
            'storage_id' => null,
            'phone' => '0901000001',
            'address' => 'Hà Nội',
            'store_name' => 'SGO Việt Nam',
            'updated_at' => now(),
        ]);
        DB::table('config')->where('id', 1)->update(['user_id' => 1]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function ensureRoles(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'administrator', 'description' => 'Administrator'],
            ['id' => 2, 'name' => 'admin_store', 'description' => 'Admin Store'],
            ['id' => 3, 'name' => 'staff', 'description' => 'Staff'],
        ]);
    }

    private function ensureAccounts(): void
    {
        foreach ([
            ['code' => '111', 'name' => 'Tiền mặt', 'level' => 1, 'parent_id' => null, 'is_default' => 1],
            ['code' => '112', 'name' => 'Tiền gửi ngân hàng', 'level' => 1, 'parent_id' => null, 'is_default' => 1],
            ['code' => '131', 'name' => 'Phải thu khách hàng', 'level' => 1, 'parent_id' => null, 'is_default' => 1],
            ['code' => '156', 'name' => 'Hàng hóa', 'level' => 1, 'parent_id' => null, 'is_default' => 1],
            ['code' => '331', 'name' => 'Phải trả nhà cung cấp', 'level' => 1, 'parent_id' => null, 'is_default' => 1],
            ['code' => '5111', 'name' => 'Doanh thu bán hàng', 'level' => 1, 'parent_id' => null, 'is_default' => 1],
        ] as $row) {
            $account = Account::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'level' => $row['level'],
                    'parent_id' => $row['parent_id'],
                    'status' => true,
                    'is_default' => $row['is_default'],
                ]
            );
            $this->accounts[$row['code']] = (int) $account->id;
        }

        $bank = Account::query()->where('code', '112')->firstOrFail();
        $bankChild = Account::query()->updateOrCreate(
            ['code' => '112DEMO'],
            [
                'name' => 'Tiền gửi ngân hàng SGO Demo - 0123456789',
                'level' => 2,
                'parent_id' => $bank->id,
                'status' => true,
                'is_default' => false,
            ]
        );
        $this->accounts['112DEMO'] = (int) $bankChild->id;
    }

    private function createUsers(): void
    {
        $this->users['administrator1'] = 1;
        $this->users['administrator2'] = $this->insertUser([
            'name' => 'Trần Hoàng Nam',
            'email' => 'administrator2@sgo.test',
            'phone' => '0901000002',
            'role_id' => 1,
        ]);

        $this->users['caugiay'] = $this->insertUser([
            'name' => 'Phạm Minh Tuấn',
            'email' => 'admin.caugiay@sgo.test',
            'phone' => '0902000001',
            'role_id' => 2,
        ]);
        $this->users['mydinh'] = $this->insertUser([
            'name' => 'Nguyễn Hải Long',
            'email' => 'admin.mydinh@sgo.test',
            'phone' => '0902000002',
            'role_id' => 2,
        ]);
        $this->users['hadong'] = $this->insertUser([
            'name' => 'Lê Quốc Anh',
            'email' => 'admin.hadong@sgo.test',
            'phone' => '0902000003',
            'role_id' => 2,
        ]);
    }

    private function insertUser(array $data): int
    {
        $id = DB::table('users')->insertGetId(array_merge([
            'manager_id' => null,
            'address' => 'Hà Nội',
            'password' => Hash::make(self::PASSWORD),
            'status' => 'active',
            'branch_id' => null,
            'storage_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $data));

        if (! Hash::check(self::PASSWORD, (string) DB::table('users')->where('id', $id)->value('password'))) {
            throw new RuntimeException('Demo password verification failed for '.$data['email']);
        }

        return (int) $id;
    }

    private function verifyPasswords(): void
    {
        $demoUsers = DB::table('users')->where('email', 'like', '%@sgo.test')->get(['email', 'password']);
        foreach ($demoUsers as $user) {
            if (! Hash::check(self::PASSWORD, (string) $user->password)) {
                throw new RuntimeException('Demo password verification failed for '.$user->email);
            }
        }
    }

    private function createBranchesAndStorages(): void
    {
        $definitions = [
            'caugiay' => [
                'name' => 'SGO Cầu Giấy',
                'address' => '46 Quan Hoa, Cầu Giấy, Hà Nội',
                'phone' => '02473000001',
                'email' => 'caugiay@sgo.test',
                'admin' => $this->users['caugiay'],
                'storages' => ['Kho SGO Cầu Giấy', 'Kho phụ Cầu Giấy'],
            ],
            'mydinh' => [
                'name' => 'SGO Mỹ Đình',
                'address' => '18 Lê Đức Thọ, Nam Từ Liêm, Hà Nội',
                'phone' => '02473000002',
                'email' => 'mydinh@sgo.test',
                'admin' => $this->users['mydinh'],
                'storages' => ['Kho SGO Mỹ Đình'],
            ],
            'hadong' => [
                'name' => 'SGO Hà Đông',
                'address' => '120 Trần Phú, Hà Đông, Hà Nội',
                'phone' => '02473000003',
                'email' => 'hadong@sgo.test',
                'admin' => $this->users['hadong'],
                'storages' => ['Kho SGO Hà Đông'],
            ],
        ];

        foreach ($definitions as $key => $definition) {
            $branchId = DB::table('branches')->insertGetId([
                'user_id' => $this->users['administrator1'],
                'admin_store_user_id' => $definition['admin'],
                'name' => $definition['name'],
                'manager_name' => $definition['name'].' - quản lý',
                'address' => $definition['address'],
                'phone' => $definition['phone'],
                'email' => $definition['email'],
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->branches[$key] = (int) $branchId;
            DB::table('users')->where('id', $definition['admin'])->update(['branch_id' => $branchId]);

            foreach ($definition['storages'] as $index => $name) {
                $storageId = DB::table('storages')->insertGetId([
                    'user_id' => $definition['admin'],
                    'branch_id' => $branchId,
                    'name' => $name,
                    'location' => $definition['address'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->storages[$key][] = (int) $storageId;
            }
        }
    }

    private function createStaff(): void
    {
        $staff = [
            'caugiay' => [
                ['Nguyễn Văn Hùng', 'nv.caugiay01@sgo.test'],
                ['Trần Minh Khôi', 'nv.caugiay02@sgo.test'],
                ['Phạm Quốc Bảo', 'kho.caugiay@sgo.test'],
                ['Nguyễn Thu Trang', 'cskh.caugiay@sgo.test'],
            ],
            'mydinh' => [
                ['Lê Văn Nam', 'nv.mydinh01@sgo.test'],
                ['Hoàng Minh Đức', 'nv.mydinh02@sgo.test'],
                ['Đỗ Anh Tuấn', 'kho.mydinh@sgo.test'],
            ],
            'hadong' => [
                ['Nguyễn Quốc Việt', 'nv.hadong01@sgo.test'],
                ['Phạm Thành Công', 'nv.hadong02@sgo.test'],
                ['Trần Đức Anh', 'kho.hadong@sgo.test'],
            ],
        ];

        foreach ($staff as $branch => $rows) {
            foreach ($rows as $index => [$name, $email]) {
                $this->insertUser([
                    'manager_id' => $this->users[$branch],
                    'name' => $name,
                    'email' => $email,
                    'phone' => '0903'.str_pad((string) (++$this->staffSequence), 6, '0', STR_PAD_LEFT),
                    'role_id' => 3,
                    'branch_id' => $this->branches[$branch],
                    'storage_id' => $this->storages[$branch][min($index, count($this->storages[$branch]) - 1)],
                ]);
            }
        }
    }

    private function createCatalog(): void
    {
        $categoryNames = ['Điện thoại', 'Máy tính bảng', 'Tai nghe', 'Sạc và cáp', 'Phụ kiện bảo vệ'];
        $brandNames = ['Apple', 'Samsung', 'Xiaomi', 'Anker', 'Spigen'];
        $categories = [];
        $brands = [];
        foreach ($categoryNames as $name) {
            $existing = DB::table('categories')->where('name', $name)->first();
            $categories[$name] = $existing
                ? (int) $existing->id
                : (int) DB::table('categories')->insertGetId([
                    'name' => $name, 'description' => 'Danh mục demo SGO', 'status' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
        }
        foreach ($brandNames as $name) {
            $existing = DB::table('brands')->where('name', $name)->first();
            $brands[$name] = $existing
                ? (int) $existing->id
                : (int) DB::table('brands')->insertGetId([
                    'name' => $name, 'description' => 'Thương hiệu demo SGO', 'status' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
        }

        $catalog = [
            ['iPhone 15 Pro Max 256GB', 'Apple', 'Điện thoại', 31990000, 27500000, 'quantity'],
            ['iPhone 15 Pro 128GB', 'Apple', 'Điện thoại', 26990000, 23200000, 'quantity'],
            ['iPhone 15 128GB', 'Apple', 'Điện thoại', 21990000, 18500000, 'quantity'],
            ['iPhone 14 128GB', 'Apple', 'Điện thoại', 17990000, 14800000, 'quantity'],
            ['iPhone 13 128GB', 'Apple', 'Điện thoại', 13990000, 11200000, 'quantity'],
            ['Galaxy S24 Ultra 256GB', 'Samsung', 'Điện thoại', 27990000, 23800000, 'quantity'],
            ['Galaxy S24 256GB', 'Samsung', 'Điện thoại', 19990000, 16600000, 'quantity'],
            ['Galaxy A55 5G', 'Samsung', 'Điện thoại', 9990000, 7600000, 'quantity'],
            ['Galaxy A35 5G', 'Samsung', 'Điện thoại', 7490000, 5600000, 'quantity'],
            ['Xiaomi 14', 'Xiaomi', 'Điện thoại', 18990000, 15400000, 'quantity'],
            ['Redmi Note 13 Pro', 'Xiaomi', 'Điện thoại', 8990000, 6800000, 'quantity'],
            ['AirPods Pro 2', 'Apple', 'Tai nghe', 5990000, 4300000, 'quantity'],
            ['AirPods 3', 'Apple', 'Tai nghe', 4490000, 3150000, 'quantity'],
            ['Cáp USB-C 20W', 'Anker', 'Sạc và cáp', 390000, 180000, 'quantity'],
            ['Sạc nhanh 20W', 'Anker', 'Sạc và cáp', 690000, 320000, 'imei'],
            ['Ốp lưng MagSafe', 'Spigen', 'Phụ kiện bảo vệ', 890000, 390000, 'imei'],
            ['Kính cường lực', 'Spigen', 'Phụ kiện bảo vệ', 250000, 80000, 'imei'],
            ['Pin dự phòng 10000mAh', 'Anker', 'Sạc và cáp', 1290000, 690000, 'imei'],
            ['Tai nghe Bluetooth', 'Xiaomi', 'Tai nghe', 1490000, 830000, 'imei'],
            ['Cáp sạc Lightning', 'Apple', 'Sạc và cáp', 590000, 270000, 'imei'],
        ];

        foreach ($catalog as $index => [$name, $brand, $category, $price, $buy, $tracking]) {
            $id = DB::table('products')->insertGetId([
                'user_id' => $this->users['administrator1'],
                'name' => $name,
                'code' => 'SGO-P'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'barcode' => '893'.str_pad((string) (self::SEED + $index), 10, '0', STR_PAD_LEFT),
                'price' => $price,
                'price_buy' => $buy,
                'thumbnail' => 'images/default-product.png',
                'product_unit' => 'cái',
                'quantity' => 0,
                'inventory_tracking' => $tracking,
                'description' => 'Sản phẩm demo đa chi nhánh SGO.',
                'is_featured' => $index < 5 ? 1 : 0,
                'is_new_arrival' => $index < 3 ? 1 : 0,
                'category_id' => $categories[$category],
                'brands_id' => $brands[$brand],
                'supplier_id' => null,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->products[] = [
                'id' => (int) $id,
                'name' => $name,
                'price' => $price,
                'buy' => $buy,
                'tracking' => $tracking,
            ];
        }
    }

    private function createParties(): void
    {
        $clientCounts = ['caugiay' => 40, 'mydinh' => 28, 'hadong' => 22];
        $clientIndex = 1;
        foreach ($clientCounts as $branch => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $id = DB::table('clients')->insertGetId([
                    'user_id' => $this->users[$branch],
                    'branch_id' => $this->branches[$branch],
                    'code' => 'KH-DEMO-'.str_pad((string) $clientIndex, 4, '0', STR_PAD_LEFT),
                    'name' => $this->faker->name(),
                    'phone' => '098'.str_pad((string) $clientIndex, 7, '0', STR_PAD_LEFT),
                    'zip_code' => '100000',
                    'address' => $this->faker->address(),
                    'email' => $i % 5 === 0 ? null : 'khach'.$clientIndex.'@demo.sgo.test',
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'dob' => Carbon::parse('1985-01-01')->addDays($clientIndex),
                    'created_at' => now()->subDays(($clientIndex * 3) % 60),
                    'updated_at' => now(),
                ]);
                $this->clients[$branch][] = (int) $id;
                $clientIndex++;
            }
        }

        $companyNames = [
            'caugiay' => [
                'Công ty TNHH Phân Phối Di Động Việt', 'Công ty CP Thiết Bị Số Hà Nội',
                'Công ty TNHH Công Nghệ Minh Phát', 'Công ty CP Phụ Kiện Việt', 'Công ty TNHH Thương Mại Hoàng Gia',
            ],
            'mydinh' => [
                'Công ty TNHH Mobile Link', 'Công ty CP Công Nghệ Đông Á',
                'Công ty TNHH Thiết Bị Thông Minh', 'Công ty CP Phân Phối Bắc Việt',
            ],
            'hadong' => [
                'Công ty TNHH Điện Tử Thành Công', 'Công ty CP Giải Pháp Số Hà Thành',
                'Công ty TNHH Thương Mại An Khang',
            ],
        ];
        $companyIndex = 1;
        $bankId = (int) (DB::table('banks')->value('id') ?: 1);
        foreach ($companyNames as $branch => $names) {
            foreach ($names as $name) {
                $companyId = DB::table('companies')->insertGetId([
                    'user_id' => $this->users[$branch],
                    'branch_id' => $this->branches[$branch],
                    'name' => $name,
                    'phone' => '024'.str_pad((string) (73010000 + $companyIndex), 8, '0', STR_PAD_LEFT),
                    'address' => $this->faker->address(),
                    'city_id' => null,
                    'email' => 'ncc'.$companyIndex.'@demo.sgo.test',
                    'tax_number' => '010'.str_pad((string) $companyIndex, 10, '0', STR_PAD_LEFT),
                    'bank_account' => '012345'.str_pad((string) $companyIndex, 4, '0', STR_PAD_LEFT),
                    'bank_id' => $bankId,
                    'note' => 'Nhà cung cấp demo Branch '.$branch,
                    'status' => 1,
                    'created_at' => now()->subDays($companyIndex),
                    'updated_at' => now(),
                ]);
                $supplierId = DB::table('suppliers')->insertGetId([
                    'company_id' => $companyId,
                    'name' => 'Đại diện '.$name,
                    'email' => 'supplier'.$companyIndex.'@demo.sgo.test',
                    'phone' => '091'.str_pad((string) $companyIndex, 7, '0', STR_PAD_LEFT),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->companies[$branch][] = ['id' => (int) $companyId, 'supplier_id' => (int) $supplierId];
                $companyIndex++;
            }
        }
    }

    private function createInventoryAndImeis(): void
    {
        foreach (['caugiay' => 76, 'mydinh' => 50, 'hadong' => 32] as $branch => $target) {
            foreach ($this->storages[$branch] as $storageId) {
                foreach ($this->products as $product) {
                    DB::table('product_storage')->insert([
                        'product_id' => $product['id'],
                        'storage_id' => $storageId,
                        'quantity' => 1000,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $availableImeis = $branch === 'caugiay' ? 28 : ($branch === 'mydinh' ? 18 : 12);
            $soldImeis = $branch === 'caugiay' ? 4 : ($branch === 'mydinh' ? 3 : 2);
            $sequence = 0;
            foreach ($this->products as $product) {
                if ($product['tracking'] !== Product::INVENTORY_TRACKING_IMEI) {
                    continue;
                }
                $count = (int) ceil(($availableImeis + $soldImeis) / 6);
                for ($i = 0; $i < $count && $sequence < $availableImeis + $soldImeis; $i++) {
                    $sequence++;
                    $status = $sequence <= $availableImeis ? ProductImei::STATUS_IN_STOCK : ProductImei::STATUS_SOLD;
                    $branchOrdinal = ['caugiay' => 1, 'mydinh' => 2, 'hadong' => 3][$branch];
                    $imei = '893'.str_pad((string) (self::SEED * 100 + $branchOrdinal * 100 + $sequence), 12, '0', STR_PAD_LEFT);
                    $imeiId = DB::table('product_imeis')->insertGetId([
                        'product_id' => $product['id'],
                        'storage_id' => $this->storages[$branch][0],
                        'import_detail_id' => null,
                        'imei' => $imei,
                        'barcode' => '290'.str_pad((string) $imei, 12, '0', STR_PAD_LEFT),
                        'status' => $status,
                        'print_count' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        // Normalize the denormalized product total to the canonical storage sum.
        DB::statement('UPDATE products p SET quantity = (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_storage ps WHERE ps.product_id = p.id)');
    }

    private function createImports(): void
    {
        $counts = ['caugiay' => 9, 'mydinh' => 6, 'hadong' => 5];
        $index = 1;
        foreach ($counts as $branch => $count) {
            foreach (range(1, $count) as $n) {
                $company = $this->companies[$branch][($n - 1) % count($this->companies[$branch])];
                $catalogOffset = $branch === 'caugiay' ? 0 : ($branch === 'mydinh' ? 5 : 13);
                $catalogSize = $branch === 'caugiay' ? 10 : ($branch === 'mydinh' ? 8 : 7);
                $product = $this->products[$catalogOffset + (($n - 1) % $catalogSize)];
                // Hà Đông imports are accessory/device batches rather than
                // single-item receipts, so every coupon remains in the
                // realistic 10m–150m range while the Branch stays smaller.
                $quantity = $branch === 'hadong' ? 130 + ($n * 10) : 2 + ($n % 4);
                $total = $product['buy'] * $quantity;
                $status = $index % 5 === 0 ? 'debt' : ($index % 3 === 0 ? 'partial' : 'paid');
                // Keep the smallest Branch's supplier exposure below the
                // medium Branch while retaining an outstanding partial debt.
                if ($branch === 'hadong' && $n === 5) {
                    $status = 'paid';
                }
                $paid = $status === 'paid' ? $total : ($status === 'partial' ? (int) floor($total * 0.45) : 0);
                $importOffset = $index >= 19
                    ? 62 + (($index - 19) * 2)
                    : (($index * 3) % 60);
                $importDate = Carbon::parse('2026-07-01')
                    ->addDays($importOffset)
                    ->setTime(8 + ($index % 9), ($index * 11) % 60, 0);
                $couponId = DB::table('import_coupon')->insertGetId([
                    'companies_id' => $company['id'],
                    'user_id' => $this->users[$branch],
                    'supplier_id' => $company['supplier_id'],
                    'total' => $total,
                    'payment_ncc' => $paid,
                    'payment_method' => $paid > 0 && $index % 2 === 0 ? 'bank_transfer' : 'cash',
                    'paid_amount' => $paid,
                    'debt_amount' => $total - $paid,
                    'payment_status' => $status,
                    'status' => 'completed',
                    'coupon_code' => 'MP-DEMO-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'storage_id' => $this->storages[$branch][0],
                    'created_at' => $importDate,
                    'updated_at' => now(),
                ]);
                $detailId = DB::table('import_detail')->insertGetId([
                    'import_id' => $couponId,
                    'product_id' => $product['id'],
                    'quantity' => $quantity,
                    'price' => $product['buy'],
                    'old_price' => $product['price'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($total - $paid > 0) {
                    DB::table('supplier_debts')->insert([
                        'companies_id' => $company['id'],
                        'branch_id' => $this->branches[$branch],
                        'supplier_id' => $company['supplier_id'],
                        'amount' => $total - $paid,
                        'description' => 'Công nợ demo theo phiếu nhập '.$couponId,
                        'code' => 'CN-DEMO-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($product['tracking'] === Product::INVENTORY_TRACKING_IMEI) {
                    for ($i = 0; $i < min($quantity, 2); $i++) {
                        DB::table('product_imeis')->where('product_id', $product['id'])->whereNull('import_detail_id')->limit(1)->update(['import_detail_id' => $detailId]);
                    }
                }

                $purchase = $this->ledgerTransaction([
                    'user_id' => $this->users[$branch],
                    'branch_id' => $this->branches[$branch],
                    'transaction_date' => $importDate->toDateString(),
                    'description' => 'Nhập hàng '.$couponId.' - '.$company['id'],
                    'reference_number' => 'IMP-'.$couponId,
                    'type' => 'expense',
                    'document_type' => 'import',
                    'created_by' => $this->users[$branch],
                    'created_at' => $importDate,
                    'updated_at' => $importDate,
                ], [
                    [$this->accounts['156'], $total, 0, Company::class, $company['id'], 'Ghi tăng hàng hóa'],
                    [$this->accounts['331'], 0, $total, Company::class, $company['id'], 'Ghi nhận công nợ nhà cung cấp'],
                ]);
                $this->orders[] = ['import_id' => $couponId, 'purchase_id' => $purchase->id];
                $index++;
            }
        }
    }

    private function createOrders(): void
    {
        $saleService = app(\App\Services\SaleService::class);
        $counts = ['caugiay' => 52, 'mydinh' => 34, 'hadong' => 21];
        $debtCustomers = ['caugiay' => 8, 'mydinh' => 5, 'hadong' => 4];
        foreach ($counts as $branch => $count) {
            $actor = User::query()->findOrFail($this->users[$branch]);
            foreach (range(0, $count - 1) as $index) {
                $date = $this->orderDate($branch, $index);
                $clientId = $this->clients[$branch][$index % count($this->clients[$branch])];
                $mainIndex = match ($branch) {
                    // Cầu Giấy: iPhone 15 128GB leads the large store.
                    'caugiay' => [2, 2, 2, 0, 1, 2, 3, 2, 5, 2][$index % 10],
                    // Mỹ Đình: Galaxy A55 leads the medium store.
                    'mydinh' => [7, 7, 7, 5, 6, 7, 8, 7, 9, 7][$index % 10],
                    // Hà Đông: iPhone 13 leads the smaller store.
                    default => [4, 4, 4, 2, 3, 4, 5, 4, 6, 4][$index % 10],
                };
                $main = $this->products[$mainIndex];
                $items = [[
                    'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
                    'product_id' => $main['id'],
                    'quantity' => 1 + ($index % 3 === 0 ? 1 : 0),
                    'unit_price' => $main['price'],
                ]];
                if ($index % 11 === 0) {
                    $extra = $this->products[10 + ($index % 4)];
                    $items[] = [
                        'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
                        'product_id' => $extra['id'],
                        'quantity' => 1,
                        'unit_price' => $extra['price'],
                    ];
                }
                $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
                $debtCustomerTarget = $debtCustomers[$branch];
                $isDebtCustomerOrder = $index < $debtCustomerTarget;
                // One target customer pays partially; the remaining target
                // customers retain debt. All other orders are fully paid.
                $isPartial = $isDebtCustomerOrder && $index === 0;
                $isDebt = $isDebtCustomerOrder && ! $isPartial;
                $method = $isDebt ? Order::PAYMENT_METHOD_DEBT : (($index % 2 === 0) ? Order::PAYMENT_METHOD_CASH : Order::PAYMENT_METHOD_BANK_TRANSFER);
                $paid = $isDebt ? 0 : ($isPartial ? (int) floor($subtotal * 0.45) : $subtotal);
                $data = [
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'discountType' => 'amount',
                    'discountInput' => 0,
                    'grand' => $subtotal,
                    'payment_method' => $method,
                    'paid_amount' => $method === Order::PAYMENT_METHOD_BANK_TRANSFER ? $paid : null,
                    'cash_tendered' => $method === Order::PAYMENT_METHOD_CASH ? $paid : null,
                    'bank_account_id' => $method === Order::PAYMENT_METHOD_BANK_TRANSFER ? $this->accounts['112DEMO'] : null,
                    'customer' => [
                        'id' => $clientId,
                        'name' => DB::table('clients')->where('id', $clientId)->value('name'),
                        'payment' => $method,
                    ],
                ];
                $order = $saleService->createPosOrder($actor, $data, $this->storages[$branch][0]);
                $orderDate = Carbon::parse($date);
                DB::table('orders')->where('id', $order->id)->update([
                    'code' => 'ODR-DEMO-'.strtoupper($branch).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
                DB::table('transactions')->where('document_type', 'order')->where('reference_number', (string) $order->id)->update([
                    'transaction_date' => $orderDate->toDateString(),
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
                $this->orders[$branch][] = (int) $order->id;
            }
        }
    }

    private function orderDate(string $branch, int $index): string
    {
        $hour = 9 + (($index + strlen($branch)) % 9);
        $minute = ($index * 13 + strlen($branch)) % 60;
        $time = sprintf('%02d:%02d:00', $hour, $minute);
        if (($branch === 'caugiay' && $index < 2) || ($branch !== 'caugiay' && $index === 0)) {
            return self::TODAY.' '.$time;
        }
        if (($branch === 'caugiay' && $index === 2)
            || ($branch !== 'caugiay' && $index === 1)
        ) {
            return '2026-09-04 '.$time;
        }

        // Bias the historical distribution toward August (busier season)
        // while retaining a smaller July tail and the explicit September
        // today orders above.
        $dayOffset = $index % 3 === 0
            ? 4 + (($index * 2 + strlen($branch)) % 24)
            : 31 + (($index * 3 + strlen($branch)) % 31);

        return Carbon::parse('2026-07-01')
            ->addDays($dayOffset)
            ->setTime($hour, $minute, 0)
            ->toDateTimeString();
    }

    private function createReturns(): void
    {
        $returnService = app(OrderReturnService::class);
        $counts = ['caugiay' => 3, 'mydinh' => 2, 'hadong' => 1];
        foreach ($counts as $branch => $count) {
            $actor = User::query()->findOrFail($this->users[$branch]);
            foreach (range(0, $count - 1) as $index) {
                $orderId = $this->orders[$branch][$index + 2] ?? $this->orders[$branch][0];
                $detail = DB::table('order_details')->where('order_id', $orderId)->first();
                if (! $detail) {
                    continue;
                }
                $return = $returnService->createReturn($actor, [
                    'original_order_id' => $orderId,
                    'return_items' => [[
                        'order_detail_id' => (int) $detail->id,
                        'quantity' => 1,
                    ]],
                    'fee_amount' => 0,
                    'note' => 'Khách đổi trả sản phẩm demo',
                ]);
                // Keep the demo dates varied while preserving the canonical
                // return/restock workflow above.
                $returnDate = Carbon::parse(self::TODAY)->subDays($index + 1)->setTime(14 + $index, 15, 0);
                DB::table('order_returns')->where('id', $return->id)->update([
                    'code' => 'RTN-DEMO-'.strtoupper($branch).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'created_at' => $returnDate,
                    'updated_at' => $returnDate,
                ]);
            }
        }
    }

    private function normalizeInventoryTargets(): void
    {
        $targets = ['caugiay' => 76, 'mydinh' => 50, 'hadong' => 32];
        foreach ($targets as $branch => $target) {
            foreach ($this->storages[$branch] as $storageId) {
                DB::table('product_storage')->where('storage_id', $storageId)->update(['quantity' => 0, 'updated_at' => now()]);
            }

            $primaryStorage = $this->storages[$branch][0];
            $imeiProducts = collect($this->products)->filter(fn ($product) => $product['tracking'] === Product::INVENTORY_TRACKING_IMEI)->values();
            $availableImeis = (int) DB::table('product_imeis as pi')
                ->join('storages as s', 's.id', '=', 'pi.storage_id')
                ->where('s.branch_id', $this->branches[$branch])
                ->where('pi.status', ProductImei::STATUS_IN_STOCK)
                ->count();
            $remaining = max($target - $availableImeis, 0);

            foreach ($imeiProducts as $product) {
                $quantity = (int) DB::table('product_imeis as pi')
                    ->join('storages as s', 's.id', '=', 'pi.storage_id')
                    ->where('s.branch_id', $this->branches[$branch])
                    ->where('pi.product_id', $product['id'])
                    ->where('pi.status', ProductImei::STATUS_IN_STOCK)
                    ->count();
                DB::table('product_storage')->where('product_id', $product['id'])->where('storage_id', $primaryStorage)->update(['quantity' => $quantity, 'updated_at' => now()]);
            }

            $quantityProducts = collect($this->products)->filter(fn ($product) => $product['tracking'] === Product::INVENTORY_TRACKING_QUANTITY)->values();
            foreach ($quantityProducts as $index => $product) {
                $quantity = $index === $quantityProducts->count() - 1
                    ? $remaining
                    : min($remaining, 2 + (($index * 3 + $this->branches[$branch]) % 7));
                DB::table('product_storage')->where('product_id', $product['id'])->where('storage_id', $primaryStorage)->update(['quantity' => $quantity, 'updated_at' => now()]);
                $remaining -= $quantity;
                if ($remaining <= 0) {
                    break;
                }
            }
        }

        DB::statement('UPDATE products p SET quantity = (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_storage ps WHERE ps.product_id = p.id)');
    }

    private function createCashAndBank(): void
    {
        $cashService = app(GenericCashVoucherService::class);
        $bankService = app(GenericBankVoucherService::class);
        $cashCounts = ['caugiay' => 16, 'mydinh' => 11, 'hadong' => 8];
        $bankCounts = ['caugiay' => 9, 'mydinh' => 6, 'hadong' => 4];
        foreach ($cashCounts as $branch => $count) {
            $actor = User::query()->findOrFail($this->users[$branch]);
            for ($i = 1; $i <= $count; $i++) {
                $direction = $i % 3 === 0 ? CashVoucher::DIRECTION_PAYMENT : CashVoucher::DIRECTION_RECEIPT;
                $amount = 250000 + ($i * 137000);
                $voucher = $cashService->create($actor, [
                    'direction' => $direction,
                    'operation' => $direction === 'receipt' ? CashVoucher::OPERATION_GENERIC_RECEIPT : CashVoucher::OPERATION_GENERIC_PAYMENT,
                    'transaction_date' => Carbon::parse('2026-07-01')->addDays(($i * 3) % 66)->toDateString(),
                    'amount' => $amount,
                    'document_type' => 'demo_cash',
                    'description' => $direction === 'receipt' ? 'Thu tiền bán phụ kiện' : 'Chi văn phòng phẩm cửa hàng',
                ]);
                $counter = $direction === 'receipt' ? $this->accounts['5111'] : $this->accounts['156'];
                $transaction = $this->ledgerTransaction([
                    'user_id' => $this->users[$branch], 'branch_id' => $this->branches[$branch],
                    'transaction_date' => $voucher->transaction_date->toDateString(),
                    'description' => $voucher->description, 'reference_number' => $voucher->voucher_number,
                    'type' => $direction === 'receipt' ? 'income' : 'expense', 'document_type' => 'cash_voucher',
                    'created_by' => $actor->id,
                ], $direction === 'receipt'
                    ? [[$this->accounts['111'], $amount, 0, null, null, 'Thu tiền mặt'], [$counter, 0, $amount, null, null, 'Đối ứng doanh thu']]
                    : [[$counter, $amount, 0, null, null, 'Chi phí/hàng hóa'], [$this->accounts['111'], 0, $amount, null, null, 'Chi tiền mặt']]);
                DB::table('cash_vouchers')->where('id', $voucher->id)->update(['accounting_transaction_id' => $transaction->id]);
            }
            for ($i = 1; $i <= $bankCounts[$branch]; $i++) {
                $direction = $i % 2 === 0 ? BankVoucher::DIRECTION_PAYMENT : BankVoucher::DIRECTION_RECEIPT;
                $amount = 750000 + ($i * 217000);
                $voucher = $bankService->create($actor, [
                    'direction' => $direction,
                    'operation' => $direction === 'receipt' ? BankVoucher::OPERATION_GENERIC_RECEIPT : BankVoucher::OPERATION_GENERIC_PAYMENT,
                    'transaction_date' => Carbon::parse('2026-07-05')->addDays(($i * 4) % 62)->toDateString(),
                    'bank_account_id' => $this->accounts['112DEMO'],
                    'amount' => $amount,
                    'document_type' => 'demo_bank',
                    'description' => $direction === 'receipt' ? 'Thu chuyển khoản bán điện thoại' : 'Chi chuyển khoản vận chuyển',
                ]);
                $counter = $direction === 'receipt' ? $this->accounts['5111'] : $this->accounts['156'];
                $transaction = $this->ledgerTransaction([
                    'user_id' => $this->users[$branch], 'branch_id' => $this->branches[$branch],
                    'transaction_date' => $voucher->transaction_date->toDateString(),
                    'description' => $voucher->description, 'reference_number' => $voucher->voucher_number,
                    'type' => $direction === 'receipt' ? 'credit_notice' : 'debit_notice', 'document_type' => 'bank_voucher',
                    'created_by' => $actor->id,
                ], $direction === 'receipt'
                    ? [[$this->accounts['112DEMO'], $amount, 0, null, null, 'Thu ngân hàng'], [$counter, 0, $amount, null, null, 'Đối ứng doanh thu']]
                    : [[$counter, $amount, 0, null, null, 'Chi phí/hàng hóa'], [$this->accounts['112DEMO'], 0, $amount, null, null, 'Chi ngân hàng']]);
                DB::table('bank_vouchers')->where('id', $voucher->id)->update(['accounting_transaction_id' => $transaction->id]);
            }
        }
    }

    private function collectCustomerDebt(): void
    {
        $service = app(CustomerDebtCollectionService::class);
        $targets = ['caugiay' => 8, 'mydinh' => 5, 'hadong' => 4];
        foreach ($targets as $branch => $count) {
            $actor = User::query()->findOrFail($this->users[$branch]);
            $debtOrders = Order::query()
                ->where('user_id', $this->users[$branch])
                ->where('branch_id', $this->branches[$branch])
                ->whereIn('payment_status', [Order::PAYMENT_STATUS_DEBT, Order::PAYMENT_STATUS_PARTIAL])
                ->orderBy('id')
                ->get();
            foreach ($debtOrders->take($count) as $index => $order) {
                $amount = max(100000, min((int) floor((int) $order->debt_amount / 3), 1500000));
                $service->collect($actor, [
                    'client_id' => (int) $order->client_id,
                    'amount' => $amount,
                    'collection_date' => self::TODAY,
                    'payment_method' => Order::PAYMENT_METHOD_CASH,
                    'idempotency_key' => sprintf('00000000-0000-4000-8000-%012d', $this->branches[$branch] * 100 + $index + 1),
                    'note' => 'Thu công nợ demo '.$branch,
                ]);
            }
        }
    }

    private function paySupplierDebt(): void
    {
        $service = app(SupplierPaymentService::class);
        // Reconcile the intended paid/partial imports through the canonical
        // payment workflow.  Intentionally unpaid (`debt`) imports stay open
        // so the UI can exercise both partial and unpaid supplier-debt paths.
        $imports = DB::table('import_coupon')
            ->whereIn('payment_status', ['paid', 'partial'])
            ->orderBy('id')
            ->get();
        foreach ($imports as $index => $import) {
            $branch = $this->branchForStorage((int) $import->storage_id);
            $amount = $import->payment_status === 'paid'
                ? max((int) $import->total, 0)
                : max((int) $import->paid_amount, 0);
            if ($amount <= 0) {
                continue;
            }
            $service->pay(User::query()->findOrFail($this->users[$branch]), [
                'import_coupon_id' => (int) $import->id,
                'amount' => $amount,
                'payment_method' => $index % 2 === 0 ? 'cash' : 'bank_transfer',
                'bank_account_id' => $index % 2 === 0 ? null : $this->accounts['112DEMO'],
                'transaction_date' => self::TODAY,
                'idempotency_key' => sprintf('00000000-0000-4000-8000-%012d', 5000 + $index + 1),
            ]);
            DB::table('supplier_debts')
                ->where('branch_id', $this->branches[$branch])
                ->where('description', '=', 'Công nợ demo theo phiếu nhập '.$import->id)
                ->update(['amount' => max((int) $import->total - $amount, 0), 'updated_at' => now()]);
        }
    }

    private function createLegacyRecords(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'user_id' => $this->users['administrator1'], 'branch_id' => null,
            'code' => 'LEGACY-DEMO-CLIENT', 'name' => 'Khách legacy chưa xác định Branch',
            'phone' => '0970000001', 'address' => 'Hà Nội', 'email' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $companyId = DB::table('companies')->insertGetId([
            'user_id' => $this->users['administrator1'], 'branch_id' => null,
            'name' => 'LEGACY-DEMO Supplier chưa xác định Branch', 'phone' => '02470000001',
            'address' => 'Hà Nội', 'email' => 'legacy-company@demo.sgo.test',
            'tax_number' => '099999999999', 'bank_account' => '0999999999',
            'bank_id' => (int) (DB::table('banks')->value('id') ?: 1), 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->ledgerTransaction([
            'user_id' => $this->users['administrator1'], 'branch_id' => null,
            'transaction_date' => self::TODAY, 'description' => 'LEGACY-DEMO transaction',
            'reference_number' => 'LEGACY-DEMO', 'type' => 'sale', 'document_type' => 'legacy_demo',
            'created_by' => $this->users['administrator1'],
        ], [
            [$this->accounts['131'], 1000000, 0, Client::class, $clientId, 'Legacy phải thu'],
            [$this->accounts['5111'], 0, 1000000, null, null, 'Legacy doanh thu'],
        ]);
        DB::table('suppliers')->insert([
            'company_id' => $companyId, 'name' => 'Đại diện legacy', 'email' => 'legacy-supplier@demo.sgo.test',
            'phone' => '0970000002', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ledgerTransaction(array $attributes, array $entries): Transaction
    {
        $transaction = Transaction::create(array_merge([
            'amount' => collect($entries)->sum(fn ($entry) => $entry[1] ?: $entry[2]),
            'status' => Transaction::STATUS_COMPLETED,
            'wallet_type' => 'demo',
            'notification' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
        foreach ($entries as [$accountId, $debit, $credit, $type, $id, $note]) {
            $transaction->entries()->create([
                'account_id' => $accountId,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'tableable_type' => $type,
                'tableable_id' => $id,
                'note' => $note,
            ]);
        }
        return $transaction;
    }

    private function branchForStorage(int $storageId): string
    {
        foreach ($this->storages as $branch => $storageIds) {
            if (in_array($storageId, $storageIds, true)) {
                return $branch;
            }
        }
        throw new RuntimeException('Storage '.$storageId.' is not mapped to a demo Branch.');
    }

    private function rebuildSnapshots(): void
    {
        $customer = app(CustomerDebtSnapshotService::class);
        $supplier = app(SupplierDebtSnapshotService::class);
        foreach (['caugiay', 'mydinh', 'hadong'] as $branch) {
            $owner = $this->users[$branch];
            $branchId = $this->branches[$branch];
            $customer->buildOwnerYear($owner, 2026, 1000, null, $branchId, true);
            $customer->buildOwnerYear($owner, 2027, 1000, null, $branchId, true);
            $supplier->buildOwnerYear($owner, 2026, 1000, null, $branchId, true);
            $supplier->buildOwnerYear($owner, 2027, 1000, null, $branchId, true);
        }
    }

    private function assertInvariants(): void
    {
        foreach (DB::table('users')->where('role_id', 3)->whereIn('email', DB::table('users')->where('role_id', 3)->pluck('email'))->get() as $staff) {
            if ($staff->branch_id === null || $staff->storage_id === null) {
                throw new RuntimeException('Staff '.$staff->email.' has incomplete Branch/Storage mapping.');
            }
            $storageBranch = DB::table('storages')->where('id', $staff->storage_id)->value('branch_id');
            if ((int) $storageBranch !== (int) $staff->branch_id) {
                throw new RuntimeException('Staff '.$staff->email.' crosses Branch and Storage.');
            }
        }
        foreach ($this->branches as $branch => $branchId) {
            $admin = DB::table('branches')->where('id', $branchId)->value('admin_store_user_id');
            if ((int) $admin !== (int) $this->users[$branch]) {
                throw new RuntimeException('Branch '.$branch.' has incorrect Admin Store mapping.');
            }
        }
        $badOrders = DB::table('orders as o')->join('order_details as od', 'od.order_id', '=', 'o.id')->join('storages as s', 's.id', '=', 'od.storage_id')->whereColumn('o.branch_id', '<>', 's.branch_id')->exists();
        if ($badOrders) {
            throw new RuntimeException('Order and sale Storage Branch mismatch detected.');
        }
        $badImports = DB::table('import_coupon as i')->join('storages as s', 's.id', '=', 'i.storage_id')->join('companies as c', 'c.id', '=', 'i.companies_id')->whereColumn('s.branch_id', '<>', 'c.branch_id')->exists();
        if ($badImports) {
            throw new RuntimeException('Import Storage and Company Branch mismatch detected.');
        }
        $badReturns = DB::table('order_returns as r')->join('orders as o', 'o.id', '=', 'r.original_order_id')->whereColumn('r.branch_id', '<>', 'o.branch_id')->exists();
        if ($badReturns) {
            throw new RuntimeException('Return and Order Branch mismatch detected.');
        }
        $badFinancial = DB::table('transactions')->whereNotNull('branch_id')->whereNotIn('branch_id', array_values($this->branches))->exists();
        if ($badFinancial) {
            throw new RuntimeException('Financial transaction points to unknown demo Branch.');
        }
        foreach (DB::table('customer_debt_yearly_snapshots')->get() as $snapshot) {
            $net = app(CustomerDebtSnapshotService::class)->fullLedgerOpeningNet((int) $snapshot->client_id, (int) $snapshot->fiscal_year, (int) $snapshot->branch_id, true);
            $snapshotNet = bcsub((string) $snapshot->opening_debit, (string) $snapshot->opening_credit, 2);
            if (bccomp($snapshotNet, $net, 2) !== 0) {
                throw new RuntimeException('Customer debt snapshot mismatch for client '.$snapshot->client_id.'.');
            }
        }
        foreach (DB::table('supplier_debt_yearly_snapshots')->get() as $snapshot) {
            $difference = app(SupplierDebtSnapshotService::class)->reconcileSnapshot(\App\Models\SupplierDebtYearlySnapshot::query()->findOrFail($snapshot->id));
            if (bccomp((string) $difference, '0.00', 2) !== 0) {
                throw new RuntimeException('Supplier debt snapshot mismatch for company '.$snapshot->company_id.'.');
            }
        }
    }

    private function summary(bool $includeLegacy): array
    {
        $rows = [];
        foreach (['caugiay', 'mydinh', 'hadong'] as $branch) {
            $branchId = $this->branches[$branch];
            $rows[$branch] = $this->dashboardRow($branchId);
        }
        $rows['global'] = $this->dashboardRow(null, $includeLegacy);
        return [
            'accounts' => DB::table('users')->where('email', 'like', '%@sgo.test')->orderBy('id')->get(['name', 'email', 'role_id', 'branch_id', 'storage_id'])->map(fn ($row) => (array) $row)->all(),
            'branches' => DB::table('branches')->orderBy('id')->get(['id', 'name', 'admin_store_user_id'])->map(fn ($row) => (array) $row)->all(),
            'storages' => DB::table('storages')->orderBy('id')->get(['id', 'branch_id', 'name', 'user_id'])->map(fn ($row) => (array) $row)->all(),
            'counts' => [
                'users' => DB::table('users')->count(), 'branches' => DB::table('branches')->count(),
                'storages' => DB::table('storages')->count(), 'clients' => DB::table('clients')->count(),
                'companies' => DB::table('companies')->count(), 'products' => DB::table('products')->count(),
                'product_imeis' => DB::table('product_imeis')->count(), 'imports' => DB::table('import_coupon')->count(),
                'orders' => DB::table('orders')->count(), 'returns' => DB::table('order_returns')->count(),
                'cash_vouchers' => DB::table('cash_vouchers')->count(), 'bank_vouchers' => DB::table('bank_vouchers')->count(),
                'transactions' => DB::table('transactions')->count(),
                'customer_snapshots' => DB::table('customer_debt_yearly_snapshots')->count(),
                'supplier_snapshots' => DB::table('supplier_debt_yearly_snapshots')->count(),
            ],
            'dashboard' => $rows,
            'password' => self::PASSWORD,
            'snapshot_reconcile' => '0.00',
        ];
    }

    private function dashboardRow(?int $branchId, bool $includeLegacy = false): array
    {
        $orders = DB::table('orders')->where('status', 1)->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId));
        $today = (clone $orders)->whereDate('created_at', self::TODAY);
        $inventory = DB::table('product_storage as ps')->join('storages as s', 's.id', '=', 'ps.storage_id')->when($branchId !== null, fn ($q) => $q->where('s.branch_id', $branchId))->sum('ps.quantity');
        $customers = DB::table('clients')->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))->when(!$includeLegacy && $branchId === null, fn ($q) => $q->whereNotNull('branch_id'))->count();
        $customerDebt = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('te.account_id', $this->accounts['131'])
            ->where('te.tableable_type', Client::class)
            ->where('t.status', Transaction::STATUS_COMPLETED)
            ->when($branchId !== null, fn ($q) => $q->where('t.branch_id', $branchId))
            ->when(!$includeLegacy && $branchId === null, fn ($q) => $q->whereNotNull('t.branch_id'))
            ->selectRaw('COALESCE(SUM(te.debit_amount - te.credit_amount), 0) AS total')
            ->value('total');
        $supplierDebt = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('te.account_id', $this->accounts['331'])
            ->where('te.tableable_type', Company::class)
            ->where('t.status', Transaction::STATUS_COMPLETED)
            ->when($branchId !== null, fn ($q) => $q->where('t.branch_id', $branchId))
            ->when(!$includeLegacy && $branchId === null, fn ($q) => $q->whereNotNull('t.branch_id'))
            ->selectRaw('COALESCE(SUM(te.credit_amount - te.debit_amount), 0) AS total')
            ->value('total');
        return [
            'orders_today' => (int) $today->count(),
            'revenue_today' => (int) $today->sum('total_money'),
            'total_revenue' => (int) $orders->sum('total_money'),
            'inventory' => (int) $inventory,
            'customers' => (int) $customers,
            'returns' => (int) DB::table('order_returns')->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))->count(),
            'customer_debt' => (int) $customerDebt,
            'supplier_debt' => (int) $supplierDebt,
            'cash' => (int) DB::table('cash_vouchers')->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))->selectRaw("COALESCE(SUM(CASE WHEN direction='receipt' THEN amount ELSE -amount END),0) AS total")->value('total'),
            'bank' => (int) DB::table('bank_vouchers')->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))->selectRaw("COALESCE(SUM(CASE WHEN direction='receipt' THEN amount ELSE -amount END),0) AS total")->value('total'),
        ];
    }

    private function writeGuide(array $summary, bool $includeLegacy): void
    {
        $lines = [
            '# SGO Multi-store demo test guide', '',
            'Generated deterministically by `php artisan demo:seed-multistore --reset`.',
            'All demo passwords: `123456`.', '', '## Login test',
        ];
        foreach ($summary['accounts'] as $account) {
            $lines[] = '- '.$account['name'].' — '.$account['email'].' — role_id='.$account['role_id'].' — branch_id='.($account['branch_id'] ?? 'NULL').' — storage_id='.($account['storage_id'] ?? 'NULL');
        }
        $lines[] = '';
        $lines[] = '## Branch / Storage matrix';
        foreach ($summary['branches'] as $branch) {
            $branchStorages = collect($summary['storages'])->where('branch_id', $branch['id'])->pluck('name')->implode(', ');
            $lines[] = '- Branch #'.$branch['id'].' '.$branch['name'].' — admin_store_user_id='.$branch['admin_store_user_id'].' — storages: '.$branchStorages;
        }
        $lines[] = '';
        $lines[] = '## Expected dashboard values calculated from the database';
        $lines[] = '| Scope | Orders today | Revenue today | Total revenue | Inventory | Customers | Returns | Customer debt | Supplier debt | Cash | Bank |';
        $lines[] = '|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|';
        foreach ($summary['dashboard'] as $scope => $row) {
            $lines[] = '| '.$scope.' | '.$row['orders_today'].' | '.$row['revenue_today'].' | '.$row['total_revenue'].' | '.$row['inventory'].' | '.$row['customers'].' | '.$row['returns'].' | '.$row['customer_debt'].' | '.$row['supplier_debt'].' | '.$row['cash'].' | '.$row['bank'].' |';
        }
        $lines[] = '';
        $lines[] = '## Manual isolation checks';
        $lines[] = '- Administrator 1 and 2 must see the same A+B+C global dataset.';
        $lines[] = '- Each Admin Store must see only its current Branch; direct ID, bulk, search and export of another Branch must fail closed.';
        $lines[] = '- Staff must be limited to its Branch and assigned Storage.';
        $lines[] = '- Legacy NULL records exist only when `--include-legacy` is supplied and are Administrator-only.';
        $lines[] = '- Snapshot reconcile is expected to be `0.00`.';
        $lines[] = '- Include legacy: '.($includeLegacy ? 'yes' : 'no').'.';
        File::ensureDirectoryExists(storage_path('app/demo'));
        File::put(storage_path('app/demo/multistore-test-guide.md'), implode(PHP_EOL, $lines).PHP_EOL);
    }
}

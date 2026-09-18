<?php

namespace Tests\Feature;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

class ProfitReportTest extends TestCase
{
    private User $storeOne;
    private User $storeTwo;
    private User $administrator;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-18 12:00:00');

        foreach (['order_return_details', 'order_returns', 'order_details', 'orders',
            'product_imeis', 'import_detail', 'products', 'storages', 'branches',
            'user_infos', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('user_infos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('img_url')->nullable();
        });
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('price_buy');
            $table->timestamps();
        });
        Schema::create('import_detail', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price');
        });
        Schema::create('product_imeis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('import_detail_id')->nullable();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('total_money');
            $table->boolean('status');
            $table->timestamps();
        });
        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_imei_id')->nullable();
            $table->unsignedBigInteger('storage_id');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price');
            $table->timestamps();
        });
        Schema::create('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('original_order_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('order_return_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_return_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('storage_id');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('return_amount');
            $table->timestamps();
        });

        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'Branch One'],
            ['id' => 2, 'name' => 'Branch Two'],
        ]);
        DB::table('storages')->insert([
            ['id' => 1, 'branch_id' => 1, 'name' => 'Storage One'],
            ['id' => 2, 'branch_id' => 2, 'name' => 'Storage Two'],
        ]);
        $this->administrator = User::create(['name' => 'Global', 'role_id' => 1]);
        $this->storeOne = User::create(['name' => 'Store One', 'role_id' => 2, 'branch_id' => 1, 'storage_id' => 1]);
        $this->storeTwo = User::create(['name' => 'Store Two', 'role_id' => 2, 'branch_id' => 2, 'storage_id' => 2]);
        $this->staff = User::create(['name' => 'Staff', 'role_id' => 3, 'branch_id' => 1, 'storage_id' => 1]);

        DB::table('products')->insert([
            ['id' => 1, 'branch_id' => 1, 'code' => 'SP-ONE', 'name' => 'Accessory One', 'price' => 100000, 'price_buy' => 150000],
            ['id' => 2, 'branch_id' => 2, 'code' => 'SP-TWO', 'name' => 'Accessory Two', 'price' => 200000, 'price_buy' => 100000],
        ]);
        $this->sale(1, 1, 1, 1, 4, 100000, '2026-09-10 10:00:00');
        $this->sale(2, 2, 2, 2, 1, 200000, '2026-09-11 10:00:00');
        $this->returnLine(1, 'completed', '2026-09-16 10:00:00');
        $this->returnLine(2, 'pending', '2026-09-17 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_get_page_renders_scoped_storages_and_script_after_jquery(): void
    {
        Event::forget('composing: *');
        View::share('config', null);
        View::share('notifications', collect());
        $this->storeOne->setRelation('userInfo', null);

        $html = $this->actingAs($this->storeOne)->get('/admin/profit')->assertOk()->getContent();
        $this->assertStringContainsString('Storage One', $html);
        $this->assertStringNotContainsString('Storage Two', $html);
        $this->storeTwo->setRelation('userInfo', null);
        $secondPage = $this->actingAs($this->storeTwo)->get('/admin/profit')->assertOk()->getContent();
        $this->assertStringContainsString('Storage Two', $secondPage);
        $this->assertStringNotContainsString('Storage One', $secondPage);
        $this->assertStringContainsString('profit-report.js', $html);
        $this->assertLessThan(
            strpos($html, 'profit-report.js'),
            strpos($html, 'jquery-3.7.1.min.js')
        );

        $this->actingAs($this->staff)->get('/admin/profit')->assertForbidden();
    }

    public function test_store_scope_and_cross_branch_storage_fail_closed(): void
    {
        $this->actingAs($this->storeTwo)
            ->postJson('/admin/profit/profit-report', ['storage_id' => 2, 'filter' => 'all'])
            ->assertOk()->assertJsonPath('product.0.product.code', 'SP-TWO');

        $this->actingAs($this->storeTwo)
            ->postJson('/admin/profit/profit-report', ['storage_id' => 1, 'filter' => 'all'])
            ->assertNotFound();
        $this->actingAs($this->storeOne)
            ->postJson('/admin/profit/profit-report-pdf', ['storage_id' => 2, 'filter' => 'all'])
            ->assertNotFound();
    }

    public function test_all_month_range_and_search_include_completed_return_only(): void
    {
        $this->actingAs($this->storeOne)
            ->postJson('/admin/profit/profit-report-all')
            ->assertOk()
            ->assertJsonPath('product.0.product.code', 'SP-ONE')
            ->assertJsonPath('product.0.quantity', 3)
            ->assertJsonPath('product.0.revenue', 300000)
            ->assertJsonPath('product.0.cost', 450000)
            ->assertJsonPath('product.0.profit', -150000);

        $this->postJson('/admin/profit/profit-report', ['storage_id' => 1, 'filter' => '3'])
            ->assertOk()->assertJsonPath('product.0.quantity', 3);
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => '6',
            'startDate' => '2026-09-10', 'endDate' => '2026-09-10',
        ])->assertOk()->assertJsonPath('product.0.quantity', 4);
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => '6',
            'startDate' => '2026-09-16', 'endDate' => '2026-09-16',
        ])->assertOk()->assertJsonPath('product.0.quantity', -1);
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => 'all', 'search' => 'SP-ONE',
        ])->assertOk()->assertJsonPath('product.0.quantity', 3);
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => 'all', 'search' => 'Accessory One',
        ])->assertOk()->assertJsonPath('product.0.quantity', 3);
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => 'all', 'search' => 'SP-TWO',
        ])->assertOk()->assertJsonCount(0, 'product');
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => 'all', 'search' => '',
        ])->assertOk()->assertJsonPath('product.0.quantity', 3);
    }

    public function test_discounted_return_uses_saved_return_amount_and_bad_branch_line_is_excluded(): void
    {
        DB::table('orders')->where('id', 1)->update(['total_money' => 360000]);
        DB::table('order_return_details')->where('id', 1)->update(['return_amount' => 90000]);

        $this->actingAs($this->storeOne)
            ->postJson('/admin/profit/profit-report', ['storage_id' => 1, 'filter' => 'all'])
            ->assertOk()->assertJsonPath('product.0.quantity', 3)
            ->assertJsonPath('product.0.revenue', 270000);

        DB::table('order_returns')->where('id', 1)->update(['branch_id' => 2]);
        $this->actingAs($this->storeOne)
            ->postJson('/admin/profit/profit-report', ['storage_id' => 1, 'filter' => 'all'])
            ->assertOk()->assertJsonPath('product.0.quantity', 4);
        DB::table('order_returns')->where('id', 1)->update(['branch_id' => 1]);

        DB::table('order_details')->where('id', 2)->update(['storage_id' => 1]);
        $this->actingAs($this->administrator)
            ->postJson('/admin/profit/profit-report-all')
            ->assertOk()->assertJsonCount(1, 'product');
    }
    public function test_invalid_filter_dates_and_storage_are_rejected(): void
    {
        $this->actingAs($this->storeOne)
            ->postJson('/admin/profit/profit-report', ['storage_id' => 1, 'filter' => 'bogus'])
            ->assertUnprocessable();
        $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => '6',
            'startDate' => '2026-09-20', 'endDate' => '2026-09-10',
        ])->assertUnprocessable();
        $this->postJson('/admin/profit/profit-report', ['storage_id' => 999, 'filter' => 'all'])
            ->assertNotFound();
        $this->postJson('/admin/profit/profit-report-pdf', ['storage_id' => 1, 'filter' => 'bogus'])
            ->assertUnprocessable();
        $this->postJson('/admin/profit/profit-report-all', ['filter' => 'bogus'])
            ->assertUnprocessable();
        $this->postJson('/admin/profit/profit-report', ['filter' => 'all'])
            ->assertUnprocessable();
    }

    public function test_administrator_is_global_and_pdf_uses_same_rows_and_scope(): void
    {
        $this->actingAs($this->administrator)
            ->postJson('/admin/profit/profit-report-all')
            ->assertOk()->assertJsonCount(2, 'product');
        $expected = $this->postJson('/admin/profit/profit-report', [
            'storage_id' => 1, 'filter' => '3', 'search' => 'SP-ONE',
        ])->assertOk()->json('product');

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('download')->once()->with('profit_report.pdf')->andReturn(response('pdf', 200));
        Pdf::shouldReceive('loadView')->once()
            ->with('admin.profit.myPDF', Mockery::on(function (array $data) use ($expected): bool {
                return $data['listprofit'][0]['quantity'] === $expected[0]['quantity']
                    && (float) $data['listprofit'][0]['revenue'] === (float) $expected[0]['revenue']
                    && $data['storage'] === 'Storage One'
                    && $data['filter'] === '3';
            }))
            ->andReturn($pdf);

        $this->withoutExceptionHandling();
        $this->post('/admin/profit/profit-report-pdf', [
            'storage_id' => 1, 'filter' => '3', 'search' => 'SP-ONE',
        ])->assertOk();
    }

    private function sale(
        int $orderId, int $detailId, int $branchId, int $storageId,
        int $quantity, int $price, string $date
    ): void {
        DB::table('orders')->insert([
            'id' => $orderId, 'branch_id' => $branchId, 'status' => 1,
            'total_money' => $quantity * $price, 'created_at' => $date, 'updated_at' => $date,
        ]);
        DB::table('order_details')->insert([
            'id' => $detailId, 'order_id' => $orderId, 'product_id' => $branchId,
            'storage_id' => $storageId, 'quantity' => $quantity, 'price' => $price,
            'created_at' => $date, 'updated_at' => $date,
        ]);
    }

    private function returnLine(int $id, string $status, string $date): void
    {
        DB::table('order_returns')->insert([
            'id' => $id, 'original_order_id' => 1, 'branch_id' => 1,
            'status' => $status, 'created_at' => $date, 'updated_at' => $date,
        ]);
        DB::table('order_return_details')->insert([
            'id' => $id, 'order_return_id' => $id, 'order_detail_id' => 1,
            'product_id' => 1, 'storage_id' => 1, 'quantity' => 1,
            'return_amount' => 100000, 'created_at' => $date, 'updated_at' => $date,
        ]);
    }
}
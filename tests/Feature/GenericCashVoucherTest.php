<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CashVoucher;
use App\Models\Roles;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CashActivityReadService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenericCashVoucherTest extends TestCase
{
    private User $owner;

    private User $otherOwner;

    private Account $cash;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-17 10:00:00');
        Schema::dropAllTables();
        $this->createSchema();

        $storeRole = Roles::create(['name' => 'administrator']);
        $this->owner = User::create([
            'name' => 'Owner A',
            'email' => 'owner-a@example.com',
            'password' => 'password',
            'status' => 'active',
            'role_id' => $storeRole->id,
        ]);
        $this->otherOwner = User::create([
            'name' => 'Owner B',
            'email' => 'owner-b@example.com',
            'password' => 'password',
            'status' => 'active',
            'role_id' => $storeRole->id,
        ]);
        $this->cash = Account::create([
            'code' => '111',
            'name' => 'Tiền mặt',
            'status' => true,
            'is_default' => true,
        ]);
        foreach (['131', '331', '5111'] as $code) {
            Account::create(['code' => $code, 'name' => "TK {$code}", 'status' => true]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_unified_cash_form_exposes_generic_metadata_and_fixed_111_without_generic_object_requirement(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.save'))
            ->assertOk()
            ->assertSee('Thu tiền thông thường')
            ->assertSee('Chi tiền thông thường')
            ->assertSee('Thu công nợ khách hàng')
            ->assertSee('Trả công nợ nhà cung cấp')
            ->assertSee('111 - Tiền mặt')
            ->assertSee('Loại chứng từ')
            ->assertSee('ID chứng từ')
            ->assertSee('generic_receipt', false)
            ->assertSee('generic_payment', false);

        $html = $response->getContent();
        $this->assertStringContainsString('id="cash-generic-account-field"', $html);
        $this->assertStringContainsString("$('#cash-object-field').toggleClass('d-none', generic)", $html);
        $this->assertStringNotContainsString('name="counter_account_id"', $html);
        $this->assertStringContainsString("title: 'Thu tiền thành công'", $html);
        $this->assertStringContainsString('allowOutsideClick: true', $html);
        $this->assertStringContainsString('res.success && isGenericReceiptMode()', $html);
    }

    public function test_generic_receipt_document_combinations_create_only_pending_vouchers(): void
    {
        $combinations = [
            [null, null],
            ['Hóa đơn', null],
            [null, 'HD001'],
            ['Hóa đơn', 'HD001'],
        ];

        foreach ($combinations as $index => [$documentType, $referenceNumber]) {
            $beforeTransactions = Schema::getConnection()->table('transactions')->count();
            $beforeEntries = Schema::getConnection()->table('transaction_entries')->count();

            $this->actingAs($this->owner)
                ->postJson(route('admin.transactions.cash.store'), [
                    'direction' => 'receipt',
                    'operation' => 'generic_receipt',
                    'transaction_date' => '2026-08-17',
                    'amount' => '500000',
                    'document_type' => $documentType,
                    'reference_number' => $referenceNumber,
                    'description' => 'Thu tiền khác',
                ])
                ->assertCreated()
                ->assertJsonPath('voucher.voucher_number', sprintf('PTTM-%06d', $index + 1))
                ->assertJsonPath('voucher.amount', '500000.00')
                ->assertJsonPath('voucher.cash_account.code', '111')
                ->assertJsonPath('voucher.cash_account.name', 'Tiền mặt')
                ->assertJsonPath('voucher.accounting_status', 'pending_accounting');

            $this->assertSame($beforeTransactions, Schema::getConnection()->table('transactions')->count());
            $this->assertSame($beforeEntries, Schema::getConnection()->table('transaction_entries')->count());
        }

        $this->assertDatabaseCount('cash_vouchers', 4);
        $this->assertDatabaseHas('cash_vouchers', [
            'voucher_number' => 'PTTM-000001',
            'document_type' => null,
            'reference_number' => null,
            'cash_account_id' => $this->cash->id,
            'accounting_status' => 'pending_accounting',
        ]);
    }

    public function test_generic_payment_uses_staff_canonical_owner_and_never_touches_131_or_331(): void
    {
        $storeRole = Roles::firstWhere('name', 'administrator');
        $staff = User::create([
            'manager_id' => $this->owner->id,
            'name' => 'Staff A',
            'email' => 'staff-a@example.com',
            'password' => 'password',
            'status' => 'active',
            'role_id' => $storeRole->id,
        ]);

        $this->actingAs($staff)
            ->postJson(route('admin.transactions.cash.store'), [
                'direction' => 'payment',
                'operation' => 'generic_payment',
                'transaction_date' => '2026-08-17',
                'amount' => '1200000',
                'document_type' => 'Hóa đơn',
                'reference_number' => 'HD-082026-001',
                'description' => 'Thanh toán tiền điện tháng 8',
            ])
            ->assertCreated()
            ->assertJsonPath('voucher.voucher_number', 'PCTM-000001');

        $voucher = CashVoucher::firstOrFail();
        $this->assertSame($this->owner->id, $voucher->owner_id);
        $this->assertSame($staff->id, $voucher->created_by);
        $this->assertSame($this->cash->id, $voucher->cash_account_id);
        $this->assertNull($voucher->counter_account_id);
        $this->assertNull($voucher->accounting_transaction_id);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
    }

    public function test_invalid_amount_operation_date_and_authoritative_fields_are_rejected(): void
    {
        $base = [
            'direction' => 'receipt',
            'operation' => 'generic_receipt',
            'transaction_date' => '2026-08-17',
            'amount' => '500000',
        ];

        foreach ([
            ['amount' => '0'],
            ['amount' => '-1'],
            ['amount' => 'abc'],
            ['operation' => 'generic_payment'],
            ['operation' => 'customer_debt_collection'],
            ['transaction_date' => '2026-08-18'],
            ['cash_account_id' => Account::where('code', '131')->value('id')],
            ['account_id' => Account::where('code', '331')->value('id')],
            ['counter_account_id' => Account::where('code', '5111')->value('id')],
            ['owner_id' => $this->otherOwner->id],
            ['accounting_status' => 'posted'],
        ] as $invalid) {
            $this->actingAs($this->owner)
                ->postJson(route('admin.transactions.cash.store'), array_merge($base, $invalid))
                ->assertUnprocessable();
        }

        $this->assertDatabaseCount('cash_vouchers', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
    }

    public function test_authentication_and_owner_isolation_protect_create_detail_and_attachment(): void
    {
        $this->postJson(route('admin.transactions.cash.store'), [])->assertUnauthorized();

        $this->actingAs($this->owner)->postJson(route('admin.transactions.cash.store'), [
            'direction' => 'receipt',
            'operation' => 'generic_receipt',
            'transaction_date' => '2026-08-17',
            'amount' => '500000',
        ])->assertCreated();

        $voucher = CashVoucher::firstOrFail();
        $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.vouchers.show', $voucher))
            ->assertOk()
            ->assertSee('PTTM-000001')
            ->assertSee('<strong>ID:</strong> '.$voucher->id, false)
            ->assertSee('<strong>Mã phiếu:</strong> PTTM-000001', false)
            ->assertSee('Chờ hạch toán')
            ->assertSee('Chưa hạch toán');

        $this->actingAs($this->otherOwner)
            ->get(route('admin.transactions.cash.vouchers.show', $voucher))
            ->assertNotFound();
        $this->actingAs($this->otherOwner)
            ->get(route('admin.transactions.cash.vouchers.attachment', $voucher))
            ->assertNotFound();
    }

    public function test_cash_list_combines_pending_rows_with_clear_columns_and_separate_totals(): void
    {
        $this->actingAs($this->owner)->postJson(route('admin.transactions.cash.store'), [
            'direction' => 'receipt',
            'operation' => 'generic_receipt',
            'transaction_date' => '2026-08-17',
            'amount' => '500000',
            'description' => 'Thu tiền khác',
        ])->assertCreated();
        $this->actingAs($this->owner)->postJson(route('admin.transactions.cash.store'), [
            'direction' => 'payment',
            'operation' => 'generic_payment',
            'transaction_date' => '2026-08-17',
            'amount' => '1200000',
            'document_type' => 'Hóa đơn',
            'reference_number' => 'HD-082026-001',
            'description' => 'Thanh toán tiền điện tháng 8',
        ])->assertCreated();

        $response = $this->actingAs($this->owner)->getJson(route('admin.transactions.cash.list', [
            'date_range' => '01/08/2026 - 31/08/2026',
        ]))->assertOk();

        $html = $response->json('html');
        $this->assertStringContainsString('Thu tiền thông thường', $html);
        $this->assertStringContainsString('Chi tiền thông thường', $html);
        $this->assertStringContainsString('Chưa hạch toán', $html);
        $this->assertStringContainsString('Chờ hạch toán', $html);
        $this->assertStringContainsString('Thu tiền khác', $html);
        $this->assertStringContainsString('Thanh toán tiền điện tháng 8', $html);
        $this->assertStringContainsString('Hóa đơn', $html);
        $this->assertStringContainsString('HD-082026-001', $html);
        $this->assertStringContainsString('500.000', $html);
        $this->assertStringContainsString('1.200.000', $html);
        $this->assertStringContainsString('Chờ hạch toán (không thuộc ledger)', $html);
    }

    public function test_cash_activity_sort_uses_business_date_then_real_creation_time_across_sources(): void
    {
        $this->createPostedCashActivity('2026-08-17', '2026-08-17 15:30:00', 'CANONICAL OLD');
        $this->createPendingVoucher('PTTM-SORT-OLD', '2026-08-17', '2026-08-17 16:00:00', 'PENDING NEW');
        $this->createPostedCashActivity('2026-08-17', '2026-08-17 17:00:00', 'CANONICAL NEW');
        $this->createPendingVoucher('PTTM-SORT-DATE', '2026-08-16', '2026-08-17 23:00:00', 'OLDER BUSINESS DATE');

        $result = app(CashActivityReadService::class)->read(
            $this->owner,
            [$this->owner->id],
            collect([$this->cash->id]),
            '2026-08-16',
            '2026-08-17',
            1,
            25,
        );

        $descriptions = $result['paginator']->getCollection()
            ->pluck('description')
            ->values()
            ->all();

        $this->assertSame([
            'CANONICAL NEW',
            'PENDING NEW',
            'CANONICAL OLD',
            'OLDER BUSINESS DATE',
        ], $descriptions);
    }

    public function test_cash_activity_sort_has_deterministic_id_tie_break_and_global_pagination(): void
    {
        $first = $this->createPendingVoucher('PTTM-TIE-001', '2026-08-17', '2026-08-17 12:00:00', 'TIE FIRST');
        $second = $this->createPendingVoucher('PTTM-TIE-002', '2026-08-17', '2026-08-17 12:00:00', 'TIE SECOND');

        $result = app(CashActivityReadService::class)->read(
            $this->owner,
            [$this->owner->id],
            collect([$this->cash->id]),
            '2026-08-17',
            '2026-08-17',
            1,
            1,
        );

        $this->assertSame($second->id, $result['paginator']->first()->sourceId);
        $this->assertSame(2, $result['paginator']->total());

        $pageTwo = app(CashActivityReadService::class)->read(
            $this->owner,
            [$this->owner->id],
            collect([$this->cash->id]),
            '2026-08-17',
            '2026-08-17',
            2,
            1,
        );

        $this->assertSame($first->id, $pageTwo['paginator']->first()->sourceId);
        $this->assertSame('TIE FIRST', $pageTwo['paginator']->first()->description);
    }

    public function test_cash_list_ui_uses_scroll_wrapper_and_bootstrap_pagination_outside_table(): void
    {
        $page = $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.index'))
            ->assertOk();

        $pageHtml = $page->getContent();
        $this->assertStringContainsString('cash-table-scroll', $pageHtml);
        $this->assertStringContainsString('cash-pagination-area', $pageHtml);
        $this->assertStringContainsString('min-width: 1890px', $pageHtml);
        $this->assertStringContainsString('overflow-x: auto', $pageHtml);
        $this->assertStringContainsString('cash-cell-clamp', $pageHtml);
        $this->assertStringContainsString('-webkit-line-clamp: 2', $pageHtml);

        for ($index = 1; $index <= 26; $index++) {
            $this->createPendingVoucher(
                sprintf('PTTM-PAGE-%03d', $index),
                '2026-08-17',
                sprintf('2026-08-17 10:%02d:00', $index),
                'PAGE '.$index,
            );
        }

        $response = $this->actingAs($this->owner)
            ->getJson(route('admin.transactions.cash.list', [
                'date_range' => '17/08/2026 - 17/08/2026',
            ]))
            ->assertOk();

        $tableHtml = $response->json('html');
        $paginationHtml = $response->json('pagination');

        $this->assertStringNotContainsString('cash-pagination', $tableHtml);
        $this->assertStringContainsString('pagination', $paginationHtml);
        $this->assertStringContainsString('page-link', $paginationHtml);
        $this->assertStringContainsString('Previous', $paginationHtml);
        $this->assertStringContainsString('Next', $paginationHtml);
        $this->assertStringNotContainsString('<svg', $paginationHtml);
    }

    public function test_cash_list_renders_numeric_source_ids_and_keeps_business_numbers_for_detail_and_reference(): void
    {
        $voucher = $this->createPendingVoucher(
            'PTTM-000001',
            '2026-08-17',
            '2026-08-17 12:00:00',
            'GENERIC ID TEST',
        );

        $clientId = DB::table('clients')->insertGetId([
            'user_id' => $this->owner->id,
            'name' => 'Customer ID Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $collectionId = DB::table('customer_debt_collections')->insertGetId([
            'client_id' => $clientId,
            'collection_number' => 'PTCN-000001',
            'created_at' => '2026-08-17 12:00:00',
            'updated_at' => '2026-08-17 12:00:00',
        ]);
        $collectionTransaction = $this->createPostedCashActivity(
            '2026-08-17',
            '2026-08-17 12:00:00',
            'CUSTOMER COLLECTION ID TEST',
            null,
            $collectionId,
        );
        $supplierTransaction = $this->createPostedCashActivity(
            '2026-08-17',
            '2026-08-17 12:00:00',
            'SUPPLIER PAYMENT ID TEST',
            'import_payment',
            null,
            'IMP-10-PAY-INITIAL',
        );
        $postedTransaction = $this->createPostedCashActivity(
            '2026-08-17',
            '2026-08-17 12:00:00',
            'POSTED TRANSACTION ID TEST',
            null,
            null,
            'CANONICAL-POSTED-001',
        );

        $result = app(CashActivityReadService::class)->read(
            $this->owner,
            [$this->owner->id],
            collect([$this->cash->id]),
            '2026-08-17',
            '2026-08-17',
            1,
            25,
        );
        $items = $result['paginator']->getCollection();

        $generic = $items->firstWhere('sourceType', 'cash_voucher');
        $customer = $items->firstWhere('sourceType', 'customer_collection');
        $supplier = $items->firstWhere('sourceType', 'supplier_payment');
        $posted = $items->firstWhere('sourceType', 'posted_transaction');

        $this->assertSame($voucher->id, $generic->sourceId);
        $this->assertSame($collectionId, $customer->sourceId);
        $this->assertSame($supplierTransaction->id, $supplier->sourceId);
        $this->assertSame($postedTransaction->id, $posted->sourceId);
        $this->assertSame('PTTM-000001', $generic->businessNumber);
        $this->assertSame('PTCN-000001', $customer->businessNumber);
        $this->assertSame('IMP-10-PAY-INITIAL', $supplier->businessNumber);
        $this->assertSame(
            route('admin.transactions.cash.vouchers.show', $voucher),
            $generic->detailUrl,
        );
        $this->assertSame(
            route('admin.debts.customer.collections.show', $collectionId),
            $customer->detailUrl,
        );
        $this->assertSame(
            route('admin.transactions.cash.posted.show', ['transactionId' => $supplierTransaction->id]),
            $supplier->detailUrl,
        );
        $this->assertSame(
            route('admin.transactions.cash.posted.show', ['transactionId' => $postedTransaction->id]),
            $posted->detailUrl,
        );
        $this->assertNotSame($generic->detailUrl, $customer->detailUrl);
        $this->assertNotSame($generic->detailUrl, $supplier->detailUrl);
        $this->assertNotSame($generic->detailUrl, $posted->detailUrl);
        $this->assertSame($collectionId, $collectionTransaction->collection_id);

        $pageHtml = $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('>ID</th>', $pageHtml);
        $this->assertStringNotContainsString('Mã/ID', $pageHtml);

        $tableHtml = $this->actingAs($this->owner)
            ->getJson(route('admin.transactions.cash.list', [
                'date_range' => '17/08/2026 - 17/08/2026',
            ]))
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('cash-col-id text-center">'.$voucher->id.'</td>', $tableHtml);
        $this->assertStringNotContainsString('PTTM-000001', $tableHtml);
        $this->assertStringContainsString('IMP-10-PAY-INITIAL', $tableHtml);
        $this->assertSame(4, substr_count($tableHtml, '>Chi tiết</a>'));
        $this->assertStringNotContainsString('class="cash-col-action text-center">—', $tableHtml);
    }

    public function test_posted_cash_detail_is_read_only_full_content_and_owner_scoped(): void
    {
        $description = 'Nội dung đầy đủ của giao dịch canonical cần được giữ nguyên trên trang chi tiết.';
        $transaction = $this->createPostedCashActivity(
            '2026-08-17',
            '2026-08-17 12:00:00',
            $description,
            null,
            null,
            'CANONICAL-DETAIL-001',
        );

        $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.posted.show', ['transactionId' => $transaction->id]))
            ->assertOk()
            ->assertSee($description)
            ->assertSee('Các dòng hạch toán')
            ->assertSee('Giao dịch tiền mặt đã hạch toán')
            ->assertDontSee('action-edit')
            ->assertDontSee('action-delete');

        $this->actingAs($this->otherOwner)
            ->get(route('admin.transactions.cash.posted.show', ['transactionId' => $transaction->id]))
            ->assertNotFound();
    }

    public function test_supplier_payment_cash_detail_uses_the_same_source_safe_transaction_route(): void
    {
        $transaction = $this->createPostedCashActivity(
            '2026-08-17',
            '2026-08-17 12:00:00',
            'Thanh toán nhà cung cấp với nội dung đầy đủ',
            'import_payment',
            null,
            'IMP-10-PAY-DETAIL',
        );

        $this->actingAs($this->owner)
            ->get(route('admin.transactions.cash.posted.show', ['transactionId' => $transaction->id]))
            ->assertOk()
            ->assertSee('Trả công nợ nhà cung cấp')
            ->assertSee('IMP-10-PAY-DETAIL');
    }

    public function test_branch_aware_cash_is_global_for_both_administrators_and_isolated_for_admin_store(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        Schema::table('transactions', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        Schema::table('cash_vouchers', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());

        $adminStoreRole = Roles::create(['name' => 'admin_store']);
        $storeA = User::create([
            'name' => 'Admin Store A',
            'email' => 'cash-store-a@example.test',
            'password' => 'password',
            'status' => 'active',
            'role_id' => $adminStoreRole->id,
            'branch_id' => 101,
        ]);
        $storeB = User::create([
            'name' => 'Admin Store B',
            'email' => 'cash-store-b@example.test',
            'password' => 'password',
            'status' => 'active',
            'role_id' => $adminStoreRole->id,
            'branch_id' => 202,
        ]);

        $makeVoucher = function (User $owner, ?int $branchId, string $number, string $description): CashVoucher {
            return CashVoucher::create([
                'owner_id' => $owner->id,
                'branch_id' => $branchId,
                'voucher_number' => $number,
                'direction' => CashVoucher::DIRECTION_RECEIPT,
                'operation' => CashVoucher::OPERATION_GENERIC_RECEIPT,
                'transaction_date' => '2026-08-17',
                'cash_account_id' => $this->cash->id,
                'amount' => '100000.00',
                'description' => $description,
                'accounting_status' => CashVoucher::STATUS_PENDING_ACCOUNTING,
                'created_by' => $owner->id,
            ]);
        };

        $voucherA = $makeVoucher($storeA, 101, 'PTTM-A', 'Cash Branch A');
        $voucherB = $makeVoucher($storeB, 202, 'PTTM-B', 'Cash Branch B');
        $legacy = $makeVoucher($this->owner, null, 'PTTM-NULL', 'Cash Legacy NULL');
        $service = app(CashActivityReadService::class);
        $read = fn (User $actor) => $service->read(
            $actor,
            [(int) $actor->ownerId()],
            collect([$this->cash->id]),
            '2026-08-01',
            '2026-08-31'
        )['paginator']->getCollection()->pluck('description')->all();

        $expectedGlobal = ['Cash Legacy NULL', 'Cash Branch B', 'Cash Branch A'];
        $this->assertEqualsCanonicalizing($expectedGlobal, $read($this->owner));
        $this->assertEqualsCanonicalizing($expectedGlobal, $read($this->otherOwner));
        $this->assertSame(['Cash Branch A'], $read($storeA));
        $this->assertSame(['Cash Branch B'], $read($storeB));

        $controller = app(\App\Http\Controllers\Admin\GenericCashVoucherController::class);
        $setActor = function (User $actor): void {
            $this->actingAs($actor);
            $request = \Illuminate\Http\Request::create('/cash-voucher', 'GET');
            $request->setUserResolver(fn () => $actor);
            $this->app->instance('request', $request);
        };

        $setActor($this->otherOwner);
        $this->assertSame($voucherA->id, $controller->show($voucherA)->getData()['voucher']->id);

        foreach ([$voucherB, $legacy] as $forbiddenVoucher) {
            $setActor($storeA);
            try {
                $controller->show($forbiddenVoucher);
                $this->fail('Admin Store A must not open Branch B or legacy NULL Cash vouchers.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(404, $exception->getStatusCode());
            }
        }
    }

    private function createPendingVoucher(
        string $voucherNumber,
        string $date,
        string $createdAt,
        string $description,
    ): CashVoucher {
        $voucher = CashVoucher::create([
            'owner_id' => $this->owner->id,
            'voucher_number' => $voucherNumber,
            'direction' => CashVoucher::DIRECTION_RECEIPT,
            'operation' => CashVoucher::OPERATION_GENERIC_RECEIPT,
            'transaction_date' => $date,
            'cash_account_id' => $this->cash->id,
            'amount' => '123000.00',
            'description' => $description,
            'accounting_status' => CashVoucher::STATUS_PENDING_ACCOUNTING,
            'created_by' => $this->owner->id,
        ]);

        $voucher->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $voucher->fresh();
    }

    private function createPostedCashActivity(
        string $date,
        string $createdAt,
        string $description,
        ?string $documentType = null,
        ?int $collectionId = null,
        ?string $referenceNumber = null,
    ): Transaction
    {
        $transaction = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => $date,
            'description' => $description,
            'reference_number' => $referenceNumber ?: 'CANONICAL-'.str_replace(' ', '-', $description),
            'type' => 'income',
            'document_type' => $documentType,
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
            'collection_id' => $collectionId,
        ]);

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();
        $transaction->entries()->create([
            'account_id' => $this->cash->id,
            'debit_amount' => '123000.00',
            'credit_amount' => '0.00',
        ]);
        $transaction->entries()->create([
            'account_id' => Account::where('code', '131')->value('id'),
            'debit_amount' => '0.00',
            'credit_amount' => '123000.00',
        ]);

        return $transaction->fresh();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::create('user_info', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('img_url')->nullable();
            $table->timestamps();
        });
        Schema::create('config', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('logo')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('level')->default(1);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        foreach (['clients', 'suppliers', 'companies'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });
        Schema::create('customer_debt_collections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('collection_number')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type');
            $table->string('document_type')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->timestamps();
        });
        Schema::create('transaction_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit_amount', 20, 2)->default(0);
            $table->decimal('credit_amount', 20, 2)->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
            $table->timestamps();
        });
        Schema::create('cash_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('voucher_number', 32);
            $table->string('direction');
            $table->string('operation');
            $table->date('transaction_date');
            $table->unsignedBigInteger('cash_account_id');
            $table->decimal('amount', 20, 2);
            $table->string('document_type')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('description')->nullable();
            $table->string('attachment')->nullable();
            $table->string('accounting_status')->default('pending_accounting');
            $table->unsignedBigInteger('counter_account_id')->nullable();
            $table->unsignedBigInteger('accounting_transaction_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->unique(['owner_id', 'voucher_number']);
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankVoucher;
use App\Models\Company;
use App\Models\Roles;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankActivityReadService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenericBankVoucherTest extends TestCase
{
    private User $owner;

    private User $otherOwner;

    private Account $bankParent;

    private Account $bank;

    private Account $cash;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-18 10:00:00');
        Schema::dropAllTables();
        $this->createSchema();

        $storeRole = Roles::create(['name' => 'store']);
        $this->owner = $this->createUser($storeRole, 'Owner A', 'owner-a@example.com');
        $this->otherOwner = $this->createUser($storeRole, 'Owner B', 'owner-b@example.com');
        $this->createAccounts();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_unified_bank_form_exposes_four_operations_and_canonical_child112_selector(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('admin.transactions.bank.save'))
            ->assertOk()
            ->assertSee('Thu tiền thông thường')
            ->assertSee('Thu công nợ khách hàng')
            ->assertSee('Chi tiền thông thường')
            ->assertSee('Trả công nợ nhà cung cấp')
            ->assertSee('112MB - MBBank')
            ->assertSee('Loại chứng từ')
            ->assertSee('ID chứng từ');

        $html = $response->getContent();
        $this->assertStringContainsString('<option value="generic_receipt"', $html);
        $this->assertStringContainsString('<option value="generic_payment"', $html);
        $this->assertStringNotContainsString('<option value="debit_notice"', $html);
        $this->assertStringNotContainsString('<option value="credit_notice"', $html);
        $this->assertStringContainsString('generic && isUnifiedBank', $html);
        $this->assertStringContainsString('bankVoucherStoreUrl', $html);
        $this->assertStringContainsString('showGenericBankSuccess', $html);
        $this->assertStringContainsString('allowOutsideClick: true', $html);
    }

    public function test_generic_receipt_document_combinations_create_pending_bank_vouchers_only(): void
    {
        $combinations = [
            [null, null],
            ['Hóa đơn', null],
            [null, 'HD001'],
            ['Hóa đơn', 'HD001'],
        ];

        foreach ($combinations as $index => [$documentType, $referenceNumber]) {
            $beforeTransactions = Transaction::count();
            $beforeEntries = DB::table('transaction_entries')->count();

            $response = $this->actingAs($this->owner)
                ->postJson(route('admin.transactions.bank.vouchers.store'), [
                    'direction' => 'receipt',
                    'operation' => 'generic_receipt',
                    'transaction_date' => '2026-08-18',
                    'bank_account_id' => $this->bank->id,
                    'amount' => '500000',
                    'document_type' => $documentType,
                    'reference_number' => $referenceNumber,
                    'description' => 'Thu ngân hàng khác',
                ])
                ->assertCreated()
                ->assertJsonPath('voucher.voucher_number', sprintf('PTNH-%06d', $index + 1))
                ->assertJsonPath('voucher.amount', '500000.00')
                ->assertJsonPath('voucher.bank_account.code', '112MB')
                ->assertJsonPath('voucher.accounting_status', 'pending_accounting');

            $this->assertArrayNotHasKey('redirect', $response->json());
            $this->assertSame($beforeTransactions, Transaction::count());
            $this->assertSame($beforeEntries, DB::table('transaction_entries')->count());
        }

        $this->assertDatabaseCount('bank_vouchers', 4);
        $this->assertDatabaseHas('bank_vouchers', [
            'voucher_number' => 'PTNH-000001',
            'direction' => 'receipt',
            'operation' => 'generic_receipt',
            'bank_account_id' => $this->bank->id,
            'accounting_status' => 'pending_accounting',
            'counter_account_id' => null,
            'accounting_transaction_id' => null,
        ]);
    }

    public function test_generic_payment_uses_canonical_owner_and_never_writes_ledger(): void
    {
        $staff = User::create([
            'manager_id' => $this->owner->id,
            'name' => 'Staff A',
            'email' => 'staff-a@example.com',
            'password' => 'password',
            'status' => 'active',
            'role_id' => $this->owner->role_id,
        ]);

        $this->actingAs($staff)
            ->postJson(route('admin.transactions.bank.vouchers.store'), [
                'direction' => 'payment',
                'operation' => 'generic_payment',
                'transaction_date' => '2026-08-18',
                'bank_account_id' => $this->bank->id,
                'amount' => '1200000',
                'description' => 'Chi ngân hàng khác',
            ])
            ->assertCreated()
            ->assertJsonPath('voucher.voucher_number', 'PCNH-000001')
            ->assertJsonPath('voucher.direction', 'payment');

        $voucher = BankVoucher::firstOrFail();
        $this->assertSame($this->owner->id, $voucher->owner_id);
        $this->assertSame($staff->id, $voucher->created_by);
        $this->assertSame($this->bank->id, $voucher->bank_account_id);
        $this->assertNull($voucher->counter_account_id);
        $this->assertNull($voucher->accounting_transaction_id);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
    }

    public function test_invalid_accounts_amount_operation_date_and_authoritative_fields_are_rejected(): void
    {
        $inactive = Account::create([
            'code' => '112OFF',
            'name' => 'Inactive Bank',
            'parent_id' => $this->bankParent->id,
            'status' => false,
        ]);
        $grandchild = Account::create([
            'code' => '112MB1',
            'name' => 'Bank Grandchild',
            'parent_id' => $this->bank->id,
            'status' => true,
        ]);
        $base = [
            'direction' => 'receipt',
            'operation' => 'generic_receipt',
            'transaction_date' => '2026-08-18',
            'bank_account_id' => $this->bank->id,
            'amount' => '500000',
        ];

        foreach ([
            ['bank_account_id' => $this->cash->id],
            ['bank_account_id' => $this->bankParent->id],
            ['bank_account_id' => $inactive->id],
            ['bank_account_id' => $grandchild->id],
            ['amount' => '0'],
            ['amount' => '-1'],
            ['amount' => 'abc'],
            ['operation' => 'generic_payment'],
            ['operation' => 'customer_debt_collection'],
            ['transaction_date' => '2026-08-19'],
            ['counter_account_id' => Account::where('code', '331')->value('id')],
            ['accounting_transaction_id' => 1],
            ['owner_id' => $this->otherOwner->id],
            ['accounting_status' => 'posted'],
            ['obj_type' => 'client'],
            ['type' => 'credit_notice'],
        ] as $invalid) {
            $this->actingAs($this->owner)
                ->postJson(route('admin.transactions.bank.vouchers.store'), array_merge($base, $invalid))
                ->assertUnprocessable();
        }

        $this->assertDatabaseCount('bank_vouchers', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_entries', 0);
    }

    public function test_attachment_detail_and_download_are_owner_safe(): void
    {
        config([
            'filesystems.disks.public.root' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'iphone-generic-bank-tests',
        ]);
        Storage::forgetDisk('public');

        $this->actingAs($this->owner)
            ->postJson(route('admin.transactions.bank.vouchers.store'), [
                'direction' => 'receipt',
                'operation' => 'generic_receipt',
                'transaction_date' => '2026-08-18',
                'bank_account_id' => $this->bank->id,
                'amount' => '500000',
                'document_type' => 'Ủy nhiệm thu',
                'reference_number' => 'UNT-001',
                'description' => 'Nội dung đầy đủ',
                'attachment' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $voucher = BankVoucher::firstOrFail();
        Storage::disk('public')->assertExists($voucher->attachment);

        $this->actingAs($this->owner)
            ->get(route('admin.transactions.bank.vouchers.show', $voucher))
            ->assertOk()
            ->assertSee('PTNH-000001')
            ->assertSee('112MB - MBBank')
            ->assertSee('Chưa hạch toán')
            ->assertSee('Nội dung đầy đủ')
            ->assertDontSee('Hạch toán pending');
        $this->actingAs($this->owner)
            ->get(route('admin.transactions.bank.vouchers.attachment', $voucher))
            ->assertOk();
        $this->actingAs($this->otherOwner)
            ->get(route('admin.transactions.bank.vouchers.show', $voucher))
            ->assertNotFound();
        $this->actingAs($this->otherOwner)
            ->get(route('admin.transactions.bank.vouchers.attachment', $voucher))
            ->assertNotFound();

        $base = [
            'direction' => 'payment',
            'operation' => 'generic_payment',
            'transaction_date' => '2026-08-18',
            'bank_account_id' => $this->bank->id,
            'amount' => '1000',
        ];
        $this->actingAs($this->owner)
            ->postJson(route('admin.transactions.bank.vouchers.store'), array_merge($base, [
                'attachment' => UploadedFile::fake()->create('bad.exe', 10, 'application/octet-stream'),
            ]))
            ->assertUnprocessable();
        $this->actingAs($this->owner)
            ->postJson(route('admin.transactions.bank.vouchers.store'), array_merge($base, [
                'attachment' => UploadedFile::fake()->create('large.pdf', 3000, 'application/pdf'),
            ]))
            ->assertUnprocessable();
    }

    public function test_bank_activity_combines_sources_sorts_globally_and_keeps_source_safe_identity(): void
    {
        $customer = $this->createPostedBankActivity(
            'customer_collection',
            '2026-08-18 15:00:00',
            'CUSTOMER POSTED'
        );
        $receipt = $this->createPendingVoucher(
            BankVoucher::DIRECTION_RECEIPT,
            '2026-08-18 15:30:00',
            'GENERIC RECEIPT'
        );
        $payment = $this->createPendingVoucher(
            BankVoucher::DIRECTION_PAYMENT,
            '2026-08-18 16:00:00',
            'GENERIC PAYMENT'
        );
        $supplier = $this->createPostedBankActivity(
            'supplier_payment',
            '2026-08-18 16:30:00',
            'SUPPLIER POSTED'
        );

        $activities = app(BankActivityReadService::class)->read(
            $this->owner,
            [$this->owner->id],
            collect([$this->bank->id]),
            '2026-08-18',
            '2026-08-18'
        );

        $this->assertSame([
            'SUPPLIER POSTED',
            'GENERIC PAYMENT',
            'GENERIC RECEIPT',
            'CUSTOMER POSTED',
        ], $activities->getCollection()->pluck('description')->all());

        $receiptItem = $activities->getCollection()->firstWhere('description', 'GENERIC RECEIPT');
        $customerItem = $activities->getCollection()->firstWhere('description', 'CUSTOMER POSTED');
        $this->assertSame($receipt->id, $receiptItem->sourceId);
        $this->assertSame($customer['collection_id'], $customerItem->sourceId);
        $this->assertSame($receipt->id, $customer['collection_id']);
        $this->assertNotSame($receiptItem->sourceType, $customerItem->sourceType);
        $this->assertNotSame($receiptItem->detailUrl, $customerItem->detailUrl);
        $this->assertSame('Chưa hạch toán', $receiptItem->counterAccountLabel);
        $this->assertSame('Chờ hạch toán', $receiptItem->accountingStatusLabel);
        $this->assertSame('Đã hạch toán', $customerItem->accountingStatusLabel);
        $this->assertSame($payment->id, $activities->getCollection()->firstWhere('description', 'GENERIC PAYMENT')->sourceId);
        $this->assertSame($supplier['transaction']->id, $activities->getCollection()->firstWhere('description', 'SUPPLIER POSTED')->sourceId);

        $html = $this->actingAs($this->owner)
            ->getJson(route('admin.transactions.bank.list', [
                'date_range' => '18/08/2026 - 18/08/2026',
            ]))
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('Thu tiền thông thường', $html);
        $this->assertStringContainsString('Chi tiền thông thường', $html);
        $this->assertStringContainsString('Thu công nợ khách hàng', $html);
        $this->assertStringContainsString('Trả công nợ nhà cung cấp', $html);
        $this->assertStringContainsString('Chờ hạch toán', $html);
        $this->assertStringContainsString('Đã hạch toán', $html);
        $this->assertStringNotContainsString($receipt->voucher_number, $html);
    }

    public function test_bank_activity_global_pagination_has_no_duplicates_or_missing_records(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            $this->createPendingVoucher(
                $index % 2 === 0 ? BankVoucher::DIRECTION_PAYMENT : BankVoucher::DIRECTION_RECEIPT,
                sprintf('2026-08-18 12:%02d:00', $index),
                'PENDING '.$index
            );
        }
        for ($index = 1; $index <= 13; $index++) {
            $this->createPostedBankActivity(
                'posted_transaction',
                sprintf('2026-08-18 13:%02d:00', $index),
                'POSTED '.$index
            );
        }

        $keys = collect();
        foreach ([1, 2, 3] as $page) {
            $paginator = app(BankActivityReadService::class)->read(
                $this->owner,
                [$this->owner->id],
                collect([$this->bank->id]),
                '2026-08-18',
                '2026-08-18',
                $page,
                10
            );
            $this->assertSame(26, $paginator->total());
            $keys = $keys->concat($paginator->getCollection()->map(
                fn ($item): string => $item->sourceType.':'.$item->sourceId
            ));
        }

        $this->assertCount(26, $keys);
        $this->assertCount(26, $keys->unique());
        $this->assertSame('POSTED 13', app(BankActivityReadService::class)->read(
            $this->owner,
            [$this->owner->id],
            collect([$this->bank->id]),
            '2026-08-18',
            '2026-08-18',
            1,
            10
        )->first()->description);
    }

    private function createPendingVoucher(string $direction, string $createdAt, string $description): BankVoucher
    {
        $receipt = $direction === BankVoucher::DIRECTION_RECEIPT;
        $voucher = BankVoucher::create([
            'owner_id' => $this->owner->id,
            'voucher_number' => ($receipt ? 'PTNH-' : 'PCNH-').str_pad(
                (string) (BankVoucher::where('direction', $direction)->count() + 1),
                6,
                '0',
                STR_PAD_LEFT
            ),
            'direction' => $direction,
            'operation' => $receipt
                ? BankVoucher::OPERATION_GENERIC_RECEIPT
                : BankVoucher::OPERATION_GENERIC_PAYMENT,
            'transaction_date' => '2026-08-18',
            'bank_account_id' => $this->bank->id,
            'amount' => '123000.00',
            'description' => $description,
            'accounting_status' => BankVoucher::STATUS_PENDING_ACCOUNTING,
            'created_by' => $this->owner->id,
        ]);
        $voucher->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $voucher->fresh();
    }

    /** @return array{transaction: Transaction, collection_id: int|null} */
    private function createPostedBankActivity(string $kind, string $createdAt, string $description): array
    {
        $collectionId = null;
        $documentType = null;
        $referenceNumber = 'BANK-'.str_replace(' ', '-', $description);
        $contraCode = '131';
        $partyType = null;
        $partyId = null;

        if ($kind === 'customer_collection') {
            $clientId = DB::table('clients')->insertGetId([
                'user_id' => $this->owner->id,
                'name' => 'Customer A',
                'phone' => '0901',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $collectionId = DB::table('customer_debt_collections')->insertGetId([
                'client_id' => $clientId,
                'collection_number' => 'PTCN-'.str_pad((string) ($clientId), 6, '0', STR_PAD_LEFT),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $partyType = 'App\\Models\\Client';
            $partyId = $clientId;
        } elseif ($kind === 'supplier_payment') {
            $company = Company::create([
                'user_id' => $this->owner->id,
                'name' => 'Company A',
                'phone' => '0902',
            ]);
            $documentType = 'import_payment';
            $referenceNumber = 'IMP-10-PAY-TEST';
            $contraCode = '331';
            $partyType = Company::class;
            $partyId = $company->id;
        }

        $payment = $kind === 'supplier_payment';
        $transaction = Transaction::create([
            'user_id' => $this->owner->id,
            'transaction_date' => '2026-08-18',
            'description' => $description,
            'reference_number' => $referenceNumber,
            'type' => $payment ? 'expense' : 'income',
            'document_type' => $documentType,
            'created_by' => $this->owner->id,
            'status' => Transaction::STATUS_COMPLETED,
            'collection_id' => $collectionId,
        ]);
        $transaction->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        $transaction->entries()->create([
            'account_id' => $this->bank->id,
            'debit_amount' => $payment ? '0.00' : '123000.00',
            'credit_amount' => $payment ? '123000.00' : '0.00',
        ]);
        $transaction->entries()->create([
            'account_id' => Account::where('code', $contraCode)->value('id'),
            'debit_amount' => $payment ? '123000.00' : '0.00',
            'credit_amount' => $payment ? '0.00' : '123000.00',
            'tableable_type' => $partyType,
            'tableable_id' => $partyId,
        ]);

        return ['transaction' => $transaction->fresh(), 'collection_id' => $collectionId];
    }

    private function createUser(Roles $role, string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'status' => 'active',
            'role_id' => $role->id,
        ]);
    }

    private function createAccounts(): void
    {
        $this->cash = Account::create(['code' => '111', 'name' => 'Cash', 'status' => true]);
        $this->bankParent = Account::create(['code' => '112', 'name' => 'Bank', 'status' => true]);
        $this->bank = Account::create([
            'code' => '112MB',
            'name' => 'MBBank',
            'parent_id' => $this->bankParent->id,
            'status' => true,
        ]);
        foreach (['131', '331', '5111'] as $code) {
            Account::create(['code' => $code, 'name' => "TK {$code}", 'status' => true]);
        }
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
                $table->boolean('status')->default(true);
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
        Schema::create('bank_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('voucher_number', 32);
            $table->string('direction');
            $table->string('operation');
            $table->date('transaction_date');
            $table->unsignedBigInteger('bank_account_id');
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

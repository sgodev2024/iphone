<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ReceiptController;
use App\Models\ClientDebt;
use App\Models\ClientDebtsDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptCustomerDebtHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'transaction_entries',
            'transactions',
            'orders',
            'receipts_detail',
            'receipts',
            'customer_debts_detail',
            'customer_debts',
            'clients',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createSchema();
    }

    public function test_paying_one_client_in_full_preserves_every_clients_debt_history(): void
    {
        [$clientA, $debtA] = $this->createClientDebt('Client A', 1000);
        [$clientB, $debtB] = $this->createClientDebt('Client B', 2000);
        [$clientC, $debtC] = $this->createClientDebt('Client C', 3000);
        $this->createUnrelatedClientData($clientB);
        $unrelatedBefore = $this->unrelatedFingerprint($clientB, $debtB, $debtC);

        $response = $this->submitReceipt($clientA, 1000);

        $this->assertSame(route('admin.quanlythuchi.receipts.index'), $response->getTargetUrl());
        $this->assertDatabaseHas('customer_debts', [
            'id' => $debtA,
            'client_id' => $clientA,
            'amount' => 0,
        ]);
        $this->assertSame(2, ClientDebtsDetail::where('customer_debts_id', $debtA)->count());
        $this->assertDatabaseHas('customer_debts_detail', [
            'customer_debts_id' => $debtA,
            'content' => 'Tạo phiếu thu',
            'amount' => -1000,
        ]);
        $this->assertSame(1, ClientDebtsDetail::where('customer_debts_id', $debtB)->count());
        $this->assertSame(1, ClientDebtsDetail::where('customer_debts_id', $debtC)->count());
        $this->assertSame($unrelatedBefore, $this->unrelatedFingerprint($clientB, $debtB, $debtC));
        $this->assertDatabaseHas('receipts', [
            'client_id' => $clientA,
            'amount_spent' => 1000,
        ]);
        $this->assertDatabaseHas('receipts_detail', [
            'content' => 'Thanh toán đủ công nợ',
            'amount' => 1000,
        ]);
    }

    public function test_partial_legacy_receipt_keeps_history_and_remaining_balance(): void
    {
        [$clientA, $debtA] = $this->createClientDebt('Client A', 1000);
        [$clientB, $debtB] = $this->createClientDebt('Client B', 2000);
        $clientBHistoryBefore = DB::table('customer_debts_detail')
            ->where('customer_debts_id', $debtB)
            ->orderBy('id')
            ->get()
            ->toArray();

        $this->submitReceipt($clientA, 400);

        $this->assertDatabaseHas('customer_debts', [
            'id' => $debtA,
            'amount' => 600,
        ]);
        $this->assertSame(2, ClientDebtsDetail::where('customer_debts_id', $debtA)->count());
        $this->assertEquals(
            $clientBHistoryBefore,
            DB::table('customer_debts_detail')
                ->where('customer_debts_id', $debtB)
                ->orderBy('id')
                ->get()
                ->toArray()
        );
    }

    private function createSchema(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('customer_debts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->string('code')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('customer_debts_detail', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_debts_id');
            $table->string('content');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
        Schema::create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('content');
            $table->decimal('amount_spent', 15, 2);
            $table->date('date_spent');
            $table->string('receipt_code')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('receipts_detail', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->string('content');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('total_money')->default(0);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });
        Schema::create('transaction_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('tableable_type')->nullable();
            $table->unsignedBigInteger('tableable_id')->nullable();
            $table->timestamps();
        });
    }

    private function createClientDebt(string $name, int $amount): array
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $debt = ClientDebt::create([
            'client_id' => $clientId,
            'amount' => $amount,
            'description' => "Công nợ {$name}",
        ]);
        ClientDebtsDetail::create([
            'customer_debts_id' => $debt->id,
            'content' => 'Phát sinh công nợ',
            'amount' => $amount,
        ]);

        return [$clientId, (int) $debt->id];
    }

    private function createUnrelatedClientData(int $clientId): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'client_id' => $clientId,
            'total_money' => 2000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transactionId = DB::table('transactions')->insertGetId([
            'document_type' => 'order',
            'reference_number' => (string) $orderId,
            'type' => 'sale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transaction_entries')->insert([
            'transaction_id' => $transactionId,
            'debit_amount' => 2000,
            'credit_amount' => 0,
            'tableable_type' => 'App\\Models\\Client',
            'tableable_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function unrelatedFingerprint(int $clientB, int $debtB, int $debtC): string
    {
        return hash('sha256', json_encode([
            'debts' => DB::table('customer_debts')->whereIn('id', [$debtB, $debtC])->orderBy('id')->get(),
            'details' => DB::table('customer_debts_detail')
                ->whereIn('customer_debts_id', [$debtB, $debtC])
                ->orderBy('id')
                ->get(),
            'orders' => DB::table('orders')->where('client_id', $clientB)->orderBy('id')->get(),
            'transactions' => DB::table('transactions')->orderBy('id')->get(),
            'entries' => DB::table('transaction_entries')->orderBy('id')->get(),
        ]));
    }

    private function submitReceipt(int $clientId, int $amount)
    {
        $request = Request::create('/admin/receipts/add', 'POST', [
            'client' => $clientId,
            'amount_spent' => $amount,
            'content' => $amount === 1000 ? 'Thanh toán đủ công nợ' : 'Thanh toán một phần',
        ]);

        return app(ReceiptController::class)->addSubmit($request);
    }
}

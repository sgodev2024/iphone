<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionSaleBusinessKeyConstraintTest extends TestCase
{
    private $migration;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('transactions');
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->default('other');
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        $this->migration = require database_path(
            'migrations/2026_08_12_160000_enforce_unique_sale_transaction_per_order.php'
        );
        $this->migration->up();
    }

    public function test_database_rejects_a_second_sale_for_the_same_order(): void
    {
        $this->insertTransaction('sale', 'order', '100');

        try {
            $this->insertTransaction('sale', 'order', '100');
            $this->fail('The database must reject a duplicate sale transaction for one order.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->getCode());
        }

        $this->assertSame(1, DB::table('transactions')->where('type', 'sale')->count());
        $this->assertSame(
            '1:100',
            DB::table('transactions')->value('sale_order_business_key')
        );
    }

    public function test_null_reference_cannot_bypass_sale_order_uniqueness(): void
    {
        $this->insertTransaction('sale', 'order', null);

        try {
            $this->insertTransaction('sale', 'order', null);
            $this->fail('A null reference must not bypass sale order uniqueness.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->getCode());
        }

        $this->assertSame('0:', DB::table('transactions')->value('sale_order_business_key'));
        $this->assertSame(1, DB::table('transactions')->where('type', 'sale')->count());
    }

    public function test_database_allows_one_sale_for_each_different_order(): void
    {
        $this->insertTransaction('sale', 'order', '100');
        $this->insertTransaction('sale', 'order', '101');

        $this->assertSame(2, DB::table('transactions')->where('type', 'sale')->count());
    }

    public function test_database_allows_multiple_payments_for_the_same_order(): void
    {
        $this->insertTransaction('income', 'order', '100');
        $this->insertTransaction('income', 'order', '100');

        $this->assertSame(2, DB::table('transactions')->where('type', 'income')->count());
        $this->assertSame(
            2,
            DB::table('transactions')->whereNull('sale_order_business_key')->count()
        );
    }

    public function test_database_allows_multiple_manual_transactions_without_a_document(): void
    {
        $this->insertTransaction('other', null, null);
        $this->insertTransaction('other', null, null);

        $this->assertSame(2, DB::table('transactions')->whereNull('document_type')->count());
        $this->assertSame(
            2,
            DB::table('transactions')->whereNull('sale_order_business_key')->count()
        );
    }

    public function test_down_removes_only_the_constraint_schema_and_preserves_rows(): void
    {
        $this->insertTransaction('sale', 'order', '100');
        $this->insertTransaction('income', 'order', '100');

        $before = DB::table('transactions')->count();

        $this->migration->down();

        $this->assertFalse(Schema::hasColumn('transactions', 'sale_order_business_key'));
        $this->assertSame($before, DB::table('transactions')->count());

        $this->insertTransaction('sale', 'order', '100');
        $this->assertSame(3, DB::table('transactions')->count());
    }

    private function insertTransaction(string $type, ?string $documentType, ?string $reference): void
    {
        DB::table('transactions')->insert([
            'user_id' => 1,
            'transaction_date' => now()->toDateString(),
            'description' => 'Constraint test',
            'reference_number' => $reference,
            'type' => $type,
            'document_type' => $documentType,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

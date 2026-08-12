<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionPurchaseBusinessKeyConstraintTest extends TestCase
{
    private object $migration;

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
            'migrations/2026_08_12_190000_enforce_unique_purchase_transaction_per_import.php'
        );
        $this->migration->up();
    }

    public function test_database_rejects_a_second_purchase_for_the_same_import(): void
    {
        $this->insertTransaction('expense', 'import', 'IMP-10');

        try {
            $this->insertTransaction('expense', 'import', 'IMP-10');
            $this->fail('The database must reject a duplicate purchase transaction for one import.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->getCode());
        }

        $this->assertSame(1, DB::table('transactions')->where('document_type', 'import')->count());
        $this->assertSame('1:IMP-10', DB::table('transactions')->value('purchase_import_business_key'));
    }

    public function test_database_allows_multiple_payments_for_the_same_import(): void
    {
        $this->insertTransaction('expense', 'import', 'IMP-10');
        $this->insertTransaction('expense', 'import_payment', 'IMP-10-PAY-INITIAL');
        $this->insertTransaction('expense', 'import_payment', 'IMP-10-PAY-SECOND');

        $this->assertSame(3, DB::table('transactions')->count());
        $this->assertSame(2, DB::table('transactions')->where('document_type', 'import_payment')->count());
    }

    public function test_down_removes_only_the_constraint_and_preserves_rows(): void
    {
        $this->insertTransaction('expense', 'import', 'IMP-10');
        $this->insertTransaction('expense', 'import_payment', 'IMP-10-PAY-INITIAL');
        $before = DB::table('transactions')->count();

        $this->migration->down();

        $this->assertFalse(Schema::hasColumn('transactions', 'purchase_import_business_key'));
        $this->assertSame($before, DB::table('transactions')->count());

        $this->insertTransaction('expense', 'import', 'IMP-10');
        $this->assertSame(3, DB::table('transactions')->count());
    }

    private function insertTransaction(string $type, string $documentType, string $reference): void
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

<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentIdempotencyMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
            $table->string('document_type')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    public function test_migration_round_trip_preserves_rows_and_enforces_scoped_idempotency(): void
    {
        DB::table('transactions')->insert([
            $this->transactionRow(1, '1'),
            $this->transactionRow(1, '2'),
        ]);
        $migration = require database_path('migrations/2026_08_12_170000_add_payment_idempotency_to_transactions.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumns('transactions', ['idempotency_key', 'idempotency_hash']));
        $this->assertSame(2, DB::table('transactions')->count());
        DB::table('transactions')->insert($this->transactionRow(1, '3'));
        DB::table('transactions')->insert($this->transactionRow(1, '4'));

        $key = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        DB::table('transactions')->insert(array_merge($this->transactionRow(1, '5'), [
            'idempotency_key' => $key,
            'idempotency_hash' => str_repeat('a', 64),
        ]));

        try {
            DB::table('transactions')->insert(array_merge($this->transactionRow(1, '6'), [
                'idempotency_key' => $key,
                'idempotency_hash' => str_repeat('a', 64),
            ]));
            $this->fail('The same owner and non-null idempotency key must be unique.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        DB::table('transactions')->insert(array_merge($this->transactionRow(2, '7'), [
            'idempotency_key' => $key,
            'idempotency_hash' => str_repeat('b', 64),
        ]));
        $countBeforeRoundTrip = DB::table('transactions')->count();

        $migration->down();
        $this->assertFalse(Schema::hasColumn('transactions', 'idempotency_key'));
        $this->assertSame($countBeforeRoundTrip, DB::table('transactions')->count());

        $migration->up();
        $this->assertTrue(Schema::hasColumns('transactions', ['idempotency_key', 'idempotency_hash']));
        $this->assertSame($countBeforeRoundTrip, DB::table('transactions')->count());
    }

    private function transactionRow(int $userId, string $reference): array
    {
        return [
            'user_id' => $userId,
            'transaction_date' => '2026-08-12',
            'reference_number' => $reference,
            'type' => 'income',
            'document_type' => 'order',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('transactions');
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('status')->default(Transaction::STATUS_PENDING);
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('notification')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function test_legacy_transaction_service_uses_schema_compatible_status_strings(): void
    {
        $service = new TransactionService(new Transaction, new User);

        $created = $service->createNewTransaction([
            'amount' => '100,000',
            'description' => 'Legacy payment',
        ], 1);

        $this->assertSame(Transaction::STATUS_COMPLETED, $created->fresh()->status);

        $pending = Transaction::create([
            'user_id' => 1,
            'description' => 'Pending transaction',
            'status' => Transaction::STATUS_PENDING,
        ]);
        $this->assertSame(
            Transaction::STATUS_COMPLETED,
            $service->confirmTransaction($pending->id)->status
        );

        $rejected = Transaction::create([
            'user_id' => 1,
            'description' => 'Rejected transaction',
            'status' => Transaction::STATUS_PENDING,
        ]);
        $this->assertSame(
            Transaction::STATUS_FAILED,
            $service->rejectTransaction($rejected->id)->status
        );
    }
}

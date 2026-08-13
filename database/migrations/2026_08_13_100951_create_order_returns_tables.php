<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            // Đơn bán ban đầu
            $table->foreignId('original_order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            // Nếu đổi hàng thì đây là đơn bán mới
            $table->foreignId('exchange_order_id')
                ->nullable()
                ->unique()
                ->constrained('orders')
                ->restrictOnDelete();

            // Snapshot phục vụ lọc/báo cáo
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Giá trị hàng khách trả sau khi tính phần giảm giá của đơn cũ
            $table->unsignedBigInteger('return_amount')->default(0);

            // Giá trị đơn hàng mới nếu có đổi
            $table->unsignedBigInteger('exchange_amount')->default(0);

            // Khoản phí/điều chỉnh nhân viên nhập
            $table->unsignedBigInteger('fee_amount')->default(0);

            // Tiền thực tế phải hoàn khách
            $table->unsignedBigInteger('refund_amount')->default(0);

            // Tiền khách phải trả thêm
            $table->unsignedBigInteger('additional_payment')->default(0);

            $table->string('status', 20)->default('completed')->index();

            $table->string('note', 1000)->nullable();

            $table->timestamps();

            $table->index(['original_order_id', 'status']);
            $table->index(['branch_id', 'created_at']);
        });


        Schema::create('order_return_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_return_id')
                ->constrained('order_returns')
                ->restrictOnDelete();

            // Rất quan trọng: trả chính xác dòng nào của đơn cũ
            $table->foreignId('order_detail_id')
                ->constrained('order_details')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_imei_id')
                ->nullable()
                ->constrained('product_imeis')
                ->restrictOnDelete();

            // Hàng phải quay lại đúng kho đã bán
            $table->foreignId('storage_id')
                ->constrained('storages')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            // Giá bán gốc trên order_details
            $table->unsignedBigInteger('original_unit_price');

            // Giá trị trước phân bổ giảm giá
            $table->unsignedBigInteger('gross_amount');

            // Phần giảm giá của đơn cũ được phân bổ cho lần trả này
            $table->unsignedBigInteger('discount_amount')->default(0);

            // Số tiền hàng thực tế được công nhận để hoàn/đổi
            $table->unsignedBigInteger('return_amount');

            $table->timestamps();

            $table->index(['order_detail_id', 'order_return_id']);
            $table->index('product_imei_id');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('order_return_details');
        Schema::dropIfExists('order_returns');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSgoZnsMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('sgo_zns_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên người nhận
            $table->string('phone'); // Số điện thoại người nhận
            $table->timestamp('sent_at'); // Ngày gửi
            $table->integer('status'); // Trạng thái tin nhắn (0 - Thất bại, 1 - Thành công)
            $table->text('note')->nullable(); // Thêm cột note
            $table->unsignedBigInteger('template_id')->nullable(); // Thêm cột template_id

            // Thiết lập khóa ngoại cho template_id
            $table->foreign('template_id')
                ->references('id')
                ->on('sgo_oa_template')
                ->onDelete('set null'); // Khi bản ghi trong sgo_oa_template bị xóa, set template_id về null

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sgo_zns_messages');
    }
}

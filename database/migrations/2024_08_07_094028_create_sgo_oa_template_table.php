<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSgoOaTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sgo_oa_template', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự động tăng
            $table->unsignedBigInteger('oa_id'); // Cột khóa ngoại
            $table->string('template_id')->unique(); // Cột template_id
            $table->string('template_name'); // Cột tên template

            // Thêm khóa ngoại
            $table->foreign('oa_id')->references('id')->on('sgo_zalo_oas')->onDelete('cascade');

            $table->timestamps(); // Tạo các cột created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sgo_oa_template');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $fields = [
            ['name' => 'Thời trang', 'short_name' => 'fashion'],
            ['name' => 'Thực phẩm', 'short_name' => 'food'],
            ['name' => 'Điện tử', 'short_name' => 'electronics'],
            ['name' => 'Du lịch', 'short_name' => 'travel'],
            ['name' => 'Bất động sản', 'short_name' => 'real_estate'],
            ['name' => 'Giáo dục', 'short_name' => 'education'],
            ['name' => 'Dịch vụ', 'short_name' => 'services'],
            ['name' => 'Nghệ thuật', 'short_name' => 'arts'],
            ['name' => 'Y tế', 'short_name' => 'health'],
            ['name' => 'Công nghệ thông tin', 'short_name' => 'technology'],
            ['name' => 'Tài chính', 'short_name' => 'finance'],
            ['name' => 'Khác', 'short_name' => 'other'],
        ];

        DB::table('fields')->insert($fields);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $fieldNames = [
            'Thời trang',
            'Thực phẩm',
            'Điện tử',
            'Du lịch',
            'Bất động sản',
            'Giáo dục',
            'Dịch vụ',
            'Nghệ thuật',
            'Y tế',
            'Công nghệ thông tin',
            'Tài chính',
            'Khác',
        ];

        DB::table('fields')->whereIn('name', $fieldNames)->delete();
    }
};

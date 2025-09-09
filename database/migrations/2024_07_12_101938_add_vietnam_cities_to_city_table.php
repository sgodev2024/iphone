<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddVietnamCitiesToCityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $cities = [
            ['name' => 'An Giang', 'short_name' => 'AG'],
            ['name' => 'Bà Rịa - Vũng Tàu', 'short_name' => 'BR-VT'],
            ['name' => 'Bạc Liêu', 'short_name' => 'BL'],
            ['name' => 'Bắc Giang', 'short_name' => 'BG'],
            ['name' => 'Bắc Kạn', 'short_name' => 'BK'],
            ['name' => 'Bắc Ninh', 'short_name' => 'BN'],
            ['name' => 'Bến Tre', 'short_name' => 'BT'],
            ['name' => 'Bình Dương', 'short_name' => 'BD'],
            ['name' => 'Bình Định', 'short_name' => 'BD'],
            ['name' => 'Bình Phước', 'short_name' => 'BP'],
            ['name' => 'Bình Thuận', 'short_name' => 'BT'],
            ['name' => 'Cà Mau', 'short_name' => 'CM'],
            ['name' => 'Cao Bằng', 'short_name' => 'CB'],
            ['name' => 'Cần Thơ', 'short_name' => 'CT'],
            ['name' => 'Đà Nẵng', 'short_name' => 'ĐN'],
            ['name' => 'Đắk Lắk', 'short_name' => 'ĐL'],
            ['name' => 'Đắk Nông', 'short_name' => 'ĐN'],
            ['name' => 'Điện Biên', 'short_name' => 'ĐB'],
            ['name' => 'Đồng Nai', 'short_name' => 'ĐN'],
            ['name' => 'Đồng Tháp', 'short_name' => 'ĐT'],
            ['name' => 'Gia Lai', 'short_name' => 'GL'],
            ['name' => 'Hà Giang', 'short_name' => 'HG'],
            ['name' => 'Hà Nam', 'short_name' => 'HN'],
            ['name' => 'Hà Nội', 'short_name' => 'HN'],
            ['name' => 'Hà Tĩnh', 'short_name' => 'HT'],
            ['name' => 'Hải Dương', 'short_name' => 'HD'],
            ['name' => 'Hải Phòng', 'short_name' => 'HP'],
            ['name' => 'Hậu Giang', 'short_name' => 'HG'],
            ['name' => 'Hòa Bình', 'short_name' => 'HB'],
            ['name' => 'Hưng Yên', 'short_name' => 'HY'],
            ['name' => 'Khánh Hòa', 'short_name' => 'KH'],
            ['name' => 'Kiên Giang', 'short_name' => 'KG'],
            ['name' => 'Kon Tum', 'short_name' => 'KT'],
            ['name' => 'Lai Châu', 'short_name' => 'LC'],
            ['name' => 'Lâm Đồng', 'short_name' => 'LD'],
            ['name' => 'Lạng Sơn', 'short_name' => 'LS'],
            ['name' => 'Lào Cai', 'short_name' => 'LC'],
            ['name' => 'Long An', 'short_name' => 'LA'],
            ['name' => 'Nam Định', 'short_name' => 'ND'],
            ['name' => 'Nghệ An', 'short_name' => 'NA'],
            ['name' => 'Ninh Bình', 'short_name' => 'NB'],
            ['name' => 'Ninh Thuận', 'short_name' => 'NT'],
            ['name' => 'Phú Thọ', 'short_name' => 'PT'],
            ['name' => 'Phú Yên', 'short_name' => 'PY'],
            ['name' => 'Quảng Bình', 'short_name' => 'QB'],
            ['name' => 'Quảng Nam', 'short_name' => 'QN'],
            ['name' => 'Quảng Ngãi', 'short_name' => 'QN'],
            ['name' => 'Quảng Ninh', 'short_name' => 'QN'],
            ['name' => 'Quảng Trị', 'short_name' => 'QT'],
            ['name' => 'Sóc Trăng', 'short_name' => 'ST'],
            ['name' => 'Sơn La', 'short_name' => 'SL'],
            ['name' => 'Tây Ninh', 'short_name' => 'TN'],
            ['name' => 'Thái Bình', 'short_name' => 'TB'],
            ['name' => 'Thái Nguyên', 'short_name' => 'TN'],
            ['name' => 'Thanh Hóa', 'short_name' => 'TH'],
            ['name' => 'Thừa Thiên Huế', 'short_name' => 'TTH'],
            ['name' => 'Tiền Giang', 'short_name' => 'TG'],
            ['name' => 'TP Hồ Chí Minh', 'short_name' => 'HCM'],
            ['name' => 'Trà Vinh', 'short_name' => 'TV'],
            ['name' => 'Tuyên Quang', 'short_name' => 'TQ'],
            ['name' => 'Vĩnh Long', 'short_name' => 'VL'],
            ['name' => 'Vĩnh Phúc', 'short_name' => 'VP'],
            ['name' => 'Yên Bái', 'short_name' => 'YB'],
        ];

        DB::table('city')->insert($cities);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $cityNames = [
            'An Giang', 'Bà Rịa - Vũng Tàu', 'Bạc Liêu', 'Bắc Giang', 'Bắc Kạn', 'Bắc Ninh', 'Bến Tre', 'Bình Dương', 'Bình Định',
            'Bình Phước', 'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Cần Thơ', 'Đà Nẵng', 'Đắk Lắk', 'Đắk Nông', 'Điện Biên',
            'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Nội', 'Hà Tĩnh', 'Hải Dương', 'Hải Phòng', 'Hậu Giang',
            'Hòa Bình', 'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An',
            'Nam Định', 'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi',
            'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa',
            'Thừa Thiên Huế', 'Tiền Giang', 'TP Hồ Chí Minh', 'Trà Vinh', 'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'
        ];

        DB::table('city')->whereIn('name', $cityNames)->delete();
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('sgo_campaign_details');
        Schema::dropIfExists('sgo_campaigns');
        Schema::dropIfExists('sgo_zns_messages');
        Schema::dropIfExists('sgo_oa_template');
        Schema::dropIfExists('sgo_zalo_oas');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
    }
};

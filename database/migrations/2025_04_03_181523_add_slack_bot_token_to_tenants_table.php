<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlackBotTokenToTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slack_bot_token')->nullable()->after('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('slack_bot_token');
        });
    }
}


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->string('slack_username')->nullable()->after('assigned_slack_user_id');
        });
    }

    public function down()
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn('slack_username');
        });
    }
};

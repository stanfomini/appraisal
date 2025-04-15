<?php

// database/migrations/2025_03_18_xxxxxx_add_stage_to_form_responses_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->string('stage')->default('submitted')->after('phone_number');
        });
    }

    public function down()
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};

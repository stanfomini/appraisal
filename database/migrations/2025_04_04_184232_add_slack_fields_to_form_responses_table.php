<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->string('assigned_slack_user_id')->nullable();
            $table->string('assigned_slack_channel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn(['assigned_slack_user_id', 'assigned_slack_channel']);
        });
    }
};

<?php

// database/migrations/YYYY_MM_DD_HHMMSS_create_comments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('form_response_id');
            $table->unsignedBigInteger('user_id');
            $table->text('comment');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('form_response_id')->references('id')->on('form_responses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
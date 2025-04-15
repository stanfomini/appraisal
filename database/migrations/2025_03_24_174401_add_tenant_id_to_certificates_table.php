<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('form_response_id');
            $table->string('path')->nullable();
            $table->timestamp('expiry')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('form_response_id')->references('id')->on('form_responses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificates');
    }
};
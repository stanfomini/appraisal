<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('certificate_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('notifiable_type');
            $table->string('notifiable_id')->nullable();
            $table->string('type');
            $table->json('data');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

          //  $table->index(['notifiable_type', 'notifiable_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificate_notifications');
    }
};
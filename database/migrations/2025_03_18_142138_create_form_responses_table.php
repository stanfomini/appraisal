<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->string('vin', 17);
            $table->string('year')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('mileage')->nullable();
            $table->string('trim_level')->nullable();
            $table->string('color')->nullable();
            $table->string('motor_size')->nullable();
            $table->string('intent')->nullable();
            $table->boolean('has_interior_features')->nullable();
            $table->text('interior_features')->nullable();
            $table->integer('interior_condition')->nullable();
            $table->text('interior_condition_comment')->nullable();
            $table->boolean('has_exterior_features')->nullable();
            $table->text('exterior_features')->nullable();
            $table->integer('body_condition')->nullable();
            $table->text('body_condition_comment')->nullable();
            $table->boolean('needs_windshield')->nullable();
            $table->boolean('needs_tires')->nullable();
            $table->boolean('has_warning_lights')->nullable();
            $table->text('warning_lights_details')->nullable();
            $table->boolean('has_mechanical_issues')->nullable();
            $table->text('mechanical_issues_details')->nullable();
            $table->json('photos')->nullable();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number', 20);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('form_responses');
    }
};
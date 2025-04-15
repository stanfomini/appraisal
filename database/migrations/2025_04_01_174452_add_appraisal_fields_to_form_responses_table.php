<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('form_responses', function (Blueprint $table) {
            // If you only need appraisal_value:
            $table->decimal('appraisal_value', 10, 2)->nullable()->after('stage');

            // If you also want to store the date/time the item was appraised:
            $table->dateTime('appraised_at')->nullable()->after('appraisal_value');

            // If you also track the user who did the appraisal:
            $table->unsignedBigInteger('appraised_by')->nullable()->after('appraised_at');
            $table->foreign('appraised_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('form_responses', function (Blueprint $table) {
            // Drop columns in reverse order
            $table->dropForeign(['appraised_by']);
            $table->dropColumn(['appraised_by','appraised_at','appraisal_value']);
        });
    }
};

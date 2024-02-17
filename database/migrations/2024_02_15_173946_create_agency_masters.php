<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $connection = 'pgsql';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_masters', function (Blueprint $table) {
            $table->id();
            $table->string('agency_name');
            $table->string('agencgy_code');
            $table->text('corresponding_address');
            $table->string('contact_person');
            $table->bigInteger('mobile');
            $table->string('gst_no');
            $table->string('pan_no');
            $table->string('photograph');
            $table->boolean('status');
            $table->timestamps('created_at');
            $table->timestamps('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agency_masters');
    }
};

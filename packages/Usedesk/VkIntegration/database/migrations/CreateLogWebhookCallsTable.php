<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogWebhookCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_webhook_calls', function (Blueprint $table) {
            $table->increments('id');

            $table->string('type')->nullable();
            $table->text('payload')->nullable();
            $table->text('exception')->nullable();

            $table->integer('webhook_id')->unsigned()->nullable();
            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('set null');
            $table->string('url');
            $table->string('payload_format')->nullable();
            $table->integer('status');
            $table->text('response');
            $table->string('response_format')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_webhook_calls');
    }
}
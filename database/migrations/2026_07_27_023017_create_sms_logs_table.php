<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sender_id', 50);
            $table->string('phone', 20);
            $table->text('message');
            $table->unsignedBigInteger('sent_by');
            $table->string('event_type', 50)->nullable();
            $table->boolean('is_auto')->default(0);
            $table->datetime('sent_at');
            $table->string('status', 20)->default('pending');
            $table->text('api_response')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('message_id', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_logs');
    }
};
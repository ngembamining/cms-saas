<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // SMS Logs
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

        // SMS Templates
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('message');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('branch_id')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        // KingoSMS Settings
        Schema::create('kingosms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_token');
            $table->string('sender_id', 11)->default('NGEMBA MINE');
            $table->string('default_sms_type', 20)->default('plain');
            $table->decimal('sms_balance', 10, 2)->default(0);
            $table->integer('credit_balance')->default(0);
            $table->datetime('last_checked')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('auto_check_balance')->default(1);
            $table->integer('balance_check_interval')->default(3600);
            $table->decimal('low_balance_alert', 10, 2)->default(1000);
            $table->boolean('low_balance_sent')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('kingosms_settings');
    }
};
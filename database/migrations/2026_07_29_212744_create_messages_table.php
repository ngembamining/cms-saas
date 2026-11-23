<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Sender (polymorphic)
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type');
            
            // Recipient (polymorphic)
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type');
            
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['sender_id', 'sender_type']);
            $table->index(['recipient_id', 'recipient_type']);
            $table->index(['recipient_id', 'recipient_type', 'is_read']);
            $table->index(['sender_id', 'sender_type', 'recipient_id', 'recipient_type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
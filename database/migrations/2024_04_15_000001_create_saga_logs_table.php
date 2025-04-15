<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saga_logs', function (Blueprint $table) {
            $table->id();
            $table->string('saga_instance_id')->index();
            $table->string('event_name');
            $table->string('status')->default('pending'); // pending, success, failed
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('compensation_handler')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['saga_instance_id', 'event_name']);
            $table->index(['saga_instance_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saga_logs');
    }
}; 
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('response_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id');
            $table->integer('status_code');
            $table->binary('raw_response')->nullable();

            $table->foreign('request_id')
                ->references('request_id')
                ->on('request_logs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_logs');
    }
};

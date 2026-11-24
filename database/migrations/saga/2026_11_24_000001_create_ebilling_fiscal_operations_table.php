<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ebilling_fiscal_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id')->unique();
            $table->uuid('saga_id')->unique();
            $table->string('operation', 40);
            $table->unsignedBigInteger('country_id');
            $table->uuid('correlation_id');
            $table->string('status', 20)->index();
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebilling_fiscal_operations');
    }
};

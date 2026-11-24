<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ebilling_operation_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('saga_id')->index();
            $table->json('payload');
            $table->string('status', 20)->default('PENDING')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebilling_operation_outbox');
    }
};

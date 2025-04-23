<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->index();
            $table->string('file_name');
            $table->string('path');
            $table->enum('type', ['request', 'response']);
            $table->string('service');
            $table->json('times')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'type', 'service'], 'unique_document_type_service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_files');
    }
}

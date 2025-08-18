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
        Schema::create('content_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->enum('file_type', ['image', 'video']);
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->json('metadata')->nullable(); // dimensions, duration, etc.
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->json('validation_results')->nullable(); // NSFW check, platform compliance
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_uploads');
    }
};

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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->enum('type', ['month', 'year']);
            $table->integer('duration')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('priority_support')->default(false);
            $table->boolean('custom_branding')->default(false);
            $table->boolean('api_access')->default(false);
            $table->integer('max_accounts_per_platform')->default(1);
            $table->integer('max_posts_per_month')->default(10);
            $table->integer('max_file_size_mb')->default(100);
            $table->integer('analytics_retention_days')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

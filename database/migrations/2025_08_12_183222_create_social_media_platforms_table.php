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
        Schema::create('social_media_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // TikTok, YouTube, Instagram, etc.
            $table->string('slug')->unique(); // tiktok, youtube, instagram, etc.
            $table->string('api_base_url');
            $table->json('required_scopes')->nullable(); // OAuth scopes needed
            $table->json('supported_media_types'); // ['video', 'image', 'text']
            $table->json('media_requirements')->nullable(); // file size limits, dimensions, etc.
            $table->boolean('supports_scheduling')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('icon_url')->nullable();
            $table->string('brand_color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_media_platforms');
    }
};

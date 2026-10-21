<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('city_geo_coordinates')) return;
        Schema::create('city_geo_coordinates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('city_id')->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('city_geo_coordinates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('managed_content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 80)->index();
            $table->enum('status', ['draft','published']);
            $table->longText('content_json');
            $table->foreignId('editor_id')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['page_key','status','id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_content_revisions');
    }
};

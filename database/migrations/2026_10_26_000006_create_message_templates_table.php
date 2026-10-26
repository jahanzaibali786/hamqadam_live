<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_templates')) {
            Schema::create('message_templates', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->index(); // interest | proposal
                $table->string('title');
                $table->text('opening')->nullable();
                $table->text('body')->nullable();
                $table->text('closing')->nullable();
                $table->string('language', 10)->default('en');
                $table->string('status', 20)->default('active'); // active | inactive
                $table->unsignedInteger('display_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['type', 'language', 'status', 'display_order'], 'message_templates_lookup');
            });
        }

        if (DB::table('message_templates')->count() === 0) {
            $rows = json_decode((string) file_get_contents(database_path('data/message_templates.json')), true) ?: [];
            $now = now();
            foreach ($rows as $row) {
                DB::table('message_templates')->insert($row + [
                    'language' => 'en',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};

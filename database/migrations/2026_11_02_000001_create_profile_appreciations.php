<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('profile_likes', function(Blueprint $t){$t->id();$t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();$t->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();$t->timestamps();$t->unique(['sender_id','recipient_id']);$t->index(['recipient_id','created_at']);});
  Schema::create('profile_super_likes', function(Blueprint $t){$t->id();$t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();$t->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();$t->unsignedInteger('coins_charged');$t->timestamps();$t->unique(['sender_id','recipient_id']);$t->index(['recipient_id','created_at']);});
 }
 public function down(): void { Schema::dropIfExists('profile_super_likes');Schema::dropIfExists('profile_likes');}
};
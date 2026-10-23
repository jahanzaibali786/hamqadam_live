<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('hamqadam_blog_subscribers',function(Blueprint $t){
   $t->id();$t->string('email',254)->unique();$t->string('unsubscribe_token',80)->unique();$t->timestamp('consented_at');$t->timestamp('unsubscribed_at')->nullable();$t->timestamps();
  });
 }
 public function down(): void {Schema::dropIfExists('hamqadam_blog_subscribers');}
};
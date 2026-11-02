<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void { if(!Schema::hasTable('expert_question_messages'))Schema::create('expert_question_messages',function(Blueprint $t){$t->id();$t->foreignId('expert_question_id')->constrained()->cascadeOnDelete();$t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();$t->text('body');$t->timestamps();$t->index(['expert_question_id','created_at']);}); }
 public function down():void {Schema::dropIfExists('expert_question_messages');}
};
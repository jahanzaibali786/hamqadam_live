<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {if(Schema::hasTable('expert_question_messages')&&!Schema::hasColumn('expert_question_messages','member_read_at'))Schema::table('expert_question_messages',function(Blueprint $t){$t->timestamp('member_read_at')->nullable()->after('body');});}
 public function down():void {if(Schema::hasTable('expert_question_messages')&&Schema::hasColumn('expert_question_messages','member_read_at'))Schema::table('expert_question_messages',fn(Blueprint $t)=>$t->dropColumn('member_read_at'));}
};
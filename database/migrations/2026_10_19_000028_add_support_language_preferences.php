<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach(['support_tickets'=>'preferred_language','help_chat_threads'=>'preferred_language'] as $table=>$column){
            if(Schema::hasTable($table) && !Schema::hasColumn($table,$column)){
                Schema::table($table,fn(Blueprint $t)=>$t->string($column,2)->nullable()->index());
            }
        }
    }
    public function down(): void {
        foreach(['support_tickets'=>'preferred_language','help_chat_threads'=>'preferred_language'] as $table=>$column){
            if(Schema::hasTable($table)&&Schema::hasColumn($table,$column)){
                Schema::table($table,fn(Blueprint $t)=>$t->dropColumn($column));
            }
        }
    }
};

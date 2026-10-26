<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('express_interests','interaction_type')) {
            Schema::table('express_interests', function(Blueprint $table) {
                $table->string('interaction_type',16)->default('interest')->index();
            });
        }
        // Previous v1 "proposal" records were stored as interests. Use the
        // proposal-specific events to identify existing rows without guessing.
        if (Schema::hasTable('proposal_events')) {
            DB::table('express_interests')->whereIn('id',DB::table('proposal_events')
                ->whereIn('event',['proposal_sent','proposal_accepted','proposal_rejected','proposal_withdrawn'])
                ->select('express_interest_id'))->update(['interaction_type'=>'proposal']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('express_interests','interaction_type')) {
            Schema::table('express_interests',fn(Blueprint $table)=>$table->dropColumn('interaction_type'));
        }
    }
};

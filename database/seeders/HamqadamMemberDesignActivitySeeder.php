<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extra local/staging-only UI scenario records. No real payment gateway writes.
 * Linked exclusively to .test accounts created by the parent seeder.
 */
final class HamqadamMemberDesignActivitySeeder extends Seeder
{
    private function demoId(string $email): ?int
    {
        $id=DB::table('users')->where('email',$email)->where('user_type','member')->value('id');
        return $id?(int)$id:null;
    }
    private function fields(string $table,array $values):array
    {
        return array_filter($values,fn($key)=>Schema::hasColumn($table,$key),ARRAY_FILTER_USE_KEY);
    }
    public function run():void
    {
        $owner=$this->demoId('demo.member.design@hamqadam.test');
        $ayesha=$this->demoId('demo.ayesha.design@hamqadam.test');
        $fatima=$this->demoId('demo.fatima.design@hamqadam.test');
        if(!$owner||!$ayesha||!$fatima) throw new \RuntimeException('Run HamqadamMemberDesignDemoSeeder first.');
        DB::transaction(function() use($owner,$ayesha,$fatima):void {
            // A realistic plan banner using the existing Gold plan configuration.
            // Assign to QA membership only, never create paid provider receipts.
            if(Schema::hasTable('packages') && Schema::hasColumn('packages','plan_tier')){
                $gold=DB::table('packages')->where('plan_tier','gold')->value('id');
                if($gold){
                    $row=$this->fields('members',[
                        'current_package_id'=>(int)$gold,'package_validity'=>now()->addDays(85)->toDateString(),
                        'auto_profile_match'=>1,'remaining_profile_viewer_view'=>120,
                        'remaining_interest'=>250,'remaining_contact_view'=>12,'remaining_photo_gallery'=>25,
                        'updated_at'=>now(),
                    ]);
                    DB::table('members')->where('user_id',$owner)->update($row);
                }
            }
            // Synthetic proposal records; no actual notification delivery.
            if(Schema::hasTable('express_interests')){
                foreach([[$owner,$ayesha,1],[$owner,$fatima,0],[$ayesha,$owner,0]] as [$from,$to,$status]){
                    if(!DB::table('express_interests')->where('user_id',$to)->where('interested_by',$from)->exists()){
                        DB::table('express_interests')->insert($this->fields('express_interests',[
                            'user_id'=>$to,'interested_by'=>$from,'status'=>$status,
                            'initial_note'=>'QA DEMO: Synthetic test proposal — not a real request.',
                            'created_at'=>now()->subDays(3),'updated_at'=>now(),
                        ]));
                    }
                }
            }
            // Conversations and their messages are intentionally non-delivering.
            if(Schema::hasTable('chat_threads')&&Schema::hasTable('chats')){
                foreach([[$ayesha,'Assalamu Alaikum. This is a QA demo conversation.'],
                          [$fatima,'Hello! Hope you are well. (Demo message)']] as [$sender,$message]){
                    $thread=DB::table('chat_threads')
                        ->where('sender_user_id',$owner)->where('receiver_user_id',$sender)->first();
                    if(!$thread){
                        $id=DB::table('chat_threads')->insertGetId($this->fields('chat_threads',[
                            'sender_user_id'=>$owner,'receiver_user_id'=>$sender,
                            'active'=>1,'created_at'=>now()->subDay(),'updated_at'=>now(),
                            'last_message_at'=>now()->subHours(3),
                        ]));
                    }else $id=(int)$thread->id;
                    if(!DB::table('chats')->where('chat_thread_id',$id)->where('message',$message)->exists()){
                        DB::table('chats')->insert($this->fields('chats',[
                            'chat_thread_id'=>$id,'sender_user_id'=>$sender,
                            'message'=>$message,'message_type'=>'text','seen'=>0,
                            'created_at'=>now()->subHours(3),'updated_at'=>now(),
                        ]));
                    }
                }
            }
            // Public story preview in staging only; clearly marked synthetic.
            if(Schema::hasTable('happy_stories')
              &&!DB::table('happy_stories')->where('title','QA DEMO: A New Beginning')->exists()){
                DB::table('happy_stories')->insert($this->fields('happy_stories',[
                    'user_id'=>$owner,'title'=>'QA DEMO: A New Beginning',
                    'partner_name'=>'QA Demo Partner',
                    'details'=>'This is an illustrative success story for staging UI testing only. It is not a real couple or a verified marriage.',
                    'approved'=>1,'photos'=>'','created_at'=>now()->subWeeks(2),'updated_at'=>now(),
                ]));
            }
        });
        $this->command?->info('Synthetic QA proposals, conversations, story and Gold plan presentation prepared.');
    }
}

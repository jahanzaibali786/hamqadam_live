<?php
declare(strict_types=1);
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Isolated QA fixture set for the redesigned member dashboard.
 * IMPORTANT: this is never registered in DatabaseSeeder.
 * Does not touch real accounts, financial ledgers or external gateways.
 */
final class HamqadamMemberDesignDemoSeeder extends Seeder
{
    private function tableRow(string $table,array $data):array
    {
        return array_filter($data,fn($key)=>Schema::hasColumn($table,$key),ARRAY_FILTER_USE_KEY);
    }

    public function run():void
    {
        HamqadamQaGuard::assertSafe();

        foreach(['users','members','profile_matches'] as $table) {
            if(!Schema::hasTable($table)) throw new \RuntimeException("Run staging migrations first; missing {$table}");
        }
        $actors=[
          ['demo.member.design@hamqadam.test','Demo','Member',1,91,'Software Engineer'],
          ['demo.ayesha.design@hamqadam.test','Ayesha','Khan',2,88,'Doctor'],
          ['demo.fatima.design@hamqadam.test','Fatima','Ahmed',2,84,'Architect'],
          ['demo.sana.design@hamqadam.test','Sana','Malik',2,79,'Teacher'],
          ['demo.noor.design@hamqadam.test','Noor','Ali',2,74,'Consultant'],
        ];
        DB::transaction(function() use ($actors):void {
            $ids=[];
            foreach($actors as [$email,$first,$last,$gender,$score,$profession]){
                $old=DB::table('users')->where('email',$email)->first();
                if($old&&$old->user_type!=='member') throw new \RuntimeException("Fixture email conflict: {$email}");
                if(!$old){
                    $row=$this->tableRow('users',[
                        'name'=>$first.' '.$last,'first_name'=>$first,'last_name'=>$last,'email'=>$email,
                        'user_type'=>'member','password'=>Hash::make(Str::random(40)),
                        'approved'=>1,'blocked'=>0,'deactivated'=>0,'membership'=>1,
                        'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
                    ]);
                    $id=DB::table('users')->insertGetId($row);
                }else $id=(int)$old->id;
                $ids[$email]=$id;
                if(!DB::table('members')->where('user_id',$id)->exists()){
                    DB::table('members')->insert($this->tableRow('members',[
                        'user_id'=>$id,'gender'=>$gender,
                        'birthday'=>$gender===1?'1997-05-10':'1998-04-07',
                        'remaining_interest'=>$gender===1?250:100,
                        'remaining_contact_view'=>20,
                        'remaining_profile_viewer_view'=>20,
                        'remaining_photo_gallery'=>25,
                        'profile_completion_percentage'=>95,
                        'hide_profile'=>0,'auto_profile_match'=>1,
                        'verification_status'=>'approved',
                        'job_title'=>$profession,'created_at'=>now(),'updated_at'=>now(),
                    ]));
                }
            }
            $owner=$ids['demo.member.design@hamqadam.test'];
            foreach(array_slice($actors,1) as [$email,$first,$last,$gender,$score]){
                $candidate=$ids[$email];
                if(!DB::table('profile_matches')->where('user_id',$owner)->where('match_id',$candidate)->exists()){
                    DB::table('profile_matches')->insert($this->tableRow('profile_matches',[
                        'user_id'=>$owner,'match_id'=>$candidate,'match_percentage'=>$score,
                        'match_status'=>'suggested','created_at'=>now(),'updated_at'=>now(),
                    ]));
                }
            }
            if(Schema::hasTable('shortlists')&&!DB::table('shortlists')->where('shortlisted_by',$owner)->exists()){
                DB::table('shortlists')->insert($this->tableRow('shortlists',[
                    'user_id'=>$ids['demo.ayesha.design@hamqadam.test'],
                    'shortlisted_by'=>$owner,'created_at'=>now(),'updated_at'=>now(),
                ]));
            }
            if(Schema::hasTable('notifications')){
                foreach(['Welcome to your refreshed Hamqadam dashboard','Your demo compatible matches are ready','Complete your profile to improve your visibility'] as $i=>$notice){
                    $key= 'hm-member-design-'.$owner.'-'.$i;
                    {
                        $payload=['title'=>'Hamqadam Demo','message'=>$notice,'fixture_key'=>$key,'type'=>'design_demo'];
                        if(!DB::table('notifications')->where('notifiable_id',$owner)->where('data','like','%'.$key.'%')->exists())
                            DB::table('notifications')->insert($this->tableRow('notifications',[
                              'id'=>(string)Str::uuid(),'type'=>'database',
                              'notifiable_type'=>\App\Models\User::class,
                              'notifiable_id'=>$owner,'data'=>json_encode($payload),
                              'read_at'=>null,'created_at'=>now()->subHours(4-$i),'updated_at'=>now(),
                            ]));
                    }
                }
            }
        });
        $this->call(HamqadamMemberDesignActivitySeeder::class);
        $this->command?->info('QA member dashboard fixture: demo.member.design@hamqadam.test');
        $this->command?->warn('No login password is published; use a locally reset test credential in staging only. No real payment, verification-provider, or marriage outcome was fabricated.');
    }
}

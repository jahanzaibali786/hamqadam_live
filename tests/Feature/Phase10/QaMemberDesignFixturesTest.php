<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;

use Database\Seeders\HamqadamMemberDesignDemoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Exercise QA fixture generation only on a synthetic in-memory test database. */
final class QaMemberDesignFixturesTest extends TestCase
{
    protected function setUp():void
    {
        parent::setUp();
        config()->set('database.connections.sqlite',[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::create('users',function(Blueprint $t):void {
            $t->id();$t->string('name');$t->string('first_name')->nullable();$t->string('last_name')->nullable();
            $t->string('email')->unique();$t->string('password');$t->string('user_type');
            $t->boolean('approved')->default(false);$t->boolean('blocked')->default(false);
            $t->boolean('deactivated')->default(false);$t->integer('membership')->default(1);
            $t->timestamp('email_verified_at')->nullable();$t->timestamps();
        });
        Schema::create('members',function(Blueprint $t):void {
            $t->id();$t->unsignedBigInteger('user_id')->unique();$t->integer('gender')->nullable();
            $t->date('birthday')->nullable();$t->unsignedInteger('remaining_interest')->default(0);
            $t->unsignedInteger('remaining_contact_view')->default(0);
            $t->unsignedInteger('remaining_profile_viewer_view')->default(0);
            $t->unsignedInteger('remaining_photo_gallery')->default(0);
            $t->unsignedInteger('profile_completion_percentage')->default(0);
            $t->boolean('hide_profile')->default(false);
            $t->boolean('auto_profile_match')->default(false);
            $t->string('verification_status')->nullable();
            $t->string('job_title')->nullable();$t->timestamps();
        });
        Schema::create('profile_matches',function(Blueprint $t):void {
            $t->id();$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('match_id');
            $t->decimal('match_percentage',5,2);$t->string('match_status')->nullable();$t->timestamps();
        });
        Schema::create('shortlists',function(Blueprint $t):void {
            $t->id();$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('shortlisted_by');$t->timestamps();
        });
        Schema::create('notifications',function(Blueprint $t):void {
            $t->uuid('id')->primary();$t->string('type');$t->string('notifiable_type');
            $t->unsignedBigInteger('notifiable_id');$t->text('data');
            $t->timestamp('read_at')->nullable();$t->timestamps();
        });
    }
    public function test_demo_seed_populates_realistic_member_views_without_duplication():void
    {
        (new HamqadamMemberDesignDemoSeeder())->run();
        $id=(int)DB::table('users')->where('email','demo.member.design@hamqadam.test')->value('id');
        $this->assertGreaterThan(0,$id);
        $this->assertSame(5,DB::table('users')->count());
        $this->assertSame(5,DB::table('members')->count());
        $this->assertSame(4,DB::table('profile_matches')->where('user_id',$id)->where('match_percentage','>',0)->count());
        $this->assertSame(4,DB::table('shortlists')->where('shortlisted_by',$id)->count());
        $this->assertSame(3,DB::table('notifications')->where('notifiable_id',$id)->count());
        (new HamqadamMemberDesignDemoSeeder())->run();
        $this->assertSame(5,DB::table('users')->count());
        $this->assertSame(4,DB::table('profile_matches')->count());
        $this->assertSame(4,DB::table('shortlists')->count());
        $this->assertSame(3,DB::table('notifications')->count());
    }
}

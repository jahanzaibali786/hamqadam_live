<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;

use App\Services\ClientScope\RequestBellNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class QaRequestBellAndMatchLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sqlite', [
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
            'foreign_key_constraints'=>true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::create('users',function(Blueprint $t):void {
            $t->id();$t->string('user_type');$t->boolean('blocked')->default(false);
            $t->softDeletes();$t->timestamps();
        });
        Schema::create('notifications',function(Blueprint $t):void {
            $t->uuid('id')->primary();$t->string('type');
            $t->morphs('notifiable');$t->text('data');
            $t->timestamp('read_at')->nullable();$t->timestamps();
        });
        DB::table('users')->insert([
            ['id'=>11,'user_type'=>'admin','blocked'=>0],
            ['id'=>12,'user_type'=>'admin','blocked'=>0],
            ['id'=>20,'user_type'=>'member','blocked'=>0],
        ]);
    }

    public function test_support_request_creates_unread_admin_bell_notifications():void
    {
        app(RequestBellNotifier::class)->created('support_ticket',52,20,'New support ticket #52','/support/support-tickets?ticket_id=52');
        $this->assertSame(2,DB::table('notifications')->count());
        foreach(DB::table('notifications')->get() as $row){
            $this->assertNull($row->read_at);
            $this->assertSame('App\\Models\\User',$row->notifiable_type);
            $json=json_decode($row->data,true);
            $this->assertSame('/support/support-tickets?ticket_id=52',$json['deep_link']);
        }
    }

    public function test_status_update_notifies_admins_and_request_owner():void
    {
        app(RequestBellNotifier::class)->updated('refund_request',41,11,20,
            'Refund request #41 is reviewing',
            '/admin/payment-refunds?refund_id=41',
            '/member/payment-refunds');
        $this->assertSame(3,DB::table('notifications')->count());
        $this->assertSame(1,DB::table('notifications')->where('notifiable_id',20)->count());
    }

    public function test_match_log_routes_are_admin_only_and_have_detail_view():void
    {
        foreach(['admin.matchmaking_logs.index','admin.matchmaking_logs.show'] as $name){
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('admin',$route->gatherMiddleware());
            $this->assertContains('auth',$route->gatherMiddleware());
        }
        $this->assertTrue(view()->exists('admin.matchmaking_logs.index'));
        $this->assertTrue(view()->exists('admin.matchmaking_logs.show'));
    }

    public function test_bell_controller_scopes_opening_by_owner():void
    {
        $source=file_get_contents(app_path('Http/Controllers/NotificationController.php'));
        $this->assertStringContainsString("->where('notifiable_id', Auth::id())",$source);
        $this->assertStringContainsString("->findOrFail(\$id)",$source);
    }
}

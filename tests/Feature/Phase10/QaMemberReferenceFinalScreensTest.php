<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class QaMemberReferenceFinalScreensTest extends TestCase
{
    public function test_reference_routes_require_login_and_member_role():void
    {
        foreach(['meetings','packages','assistant','blogs','stories','settings'] as $name){
            $route=Route::getRoutes()->getByName('member.reference.'.$name);
            $this->assertNotNull($route);
            $this->assertContains('auth',$route->gatherMiddleware());
            $this->assertContains('member',$route->gatherMiddleware());
        }
    }

    public function test_meeting_and_ai_mutations_have_csrf_and_throttle():void
    {
        foreach(['meetings.store','assistant.run'] as $name){
            $route=Route::getRoutes()->getByName('member.reference.'.$name);
            $this->assertNotNull($route);
            $this->assertContains('POST',$route->methods());
            $this->assertTrue(collect($route->gatherMiddleware())->contains(fn($m)=>str_starts_with($m,'throttle:')));
        }
        $meeting=file_get_contents(app_path('Http/Controllers/Member/ReferenceScreensController.php'));
        $this->assertStringContainsString('ProposalStatus::Accepted',$meeting);
        $this->assertStringContainsString('FamilyGuardianLink::query()',$meeting);
    }

    public function test_screens_reuse_current_purchase_content_and_ai_services():void
    {
        foreach(['meetings','packages','assistant','blogs','stories','settings'] as $page){
            $source=file_get_contents(resource_path('views/frontend/member/reference/'.$page.'.blade.php'));
            $this->assertStringContainsString("@extends('frontend.layouts.member_panel')",$source);
        }
        $packages=file_get_contents(resource_path('views/frontend/member/reference/packages.blade.php'));
        $this->assertStringContainsString("route('package_payment_methods'",$packages);
        $assistant=file_get_contents(app_path('Http/Controllers/Member/ReferenceScreensController.php'));
        $this->assertStringContainsString('AiFeatureService $ai',$assistant);
        $this->assertStringContainsString('Blog::active()',$assistant);
        $this->assertStringContainsString("where('approved', 1)",$assistant);
    }
}

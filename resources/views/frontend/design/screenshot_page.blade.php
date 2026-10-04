<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $pageTitle ?? get_setting('website_name', 'Hamqadam') }}</title>
<meta name="theme-color" content="#b94f7d">
<style>
*{box-sizing:border-box}html,body{margin:0;background:#fffafb;color:#422936;font-family:Inter,Arial,sans-serif}.design-shell{width:100%;overflow:hidden}.design-stage{position:relative;width:min(100%,1280px);margin:0 auto;background:#fff}.design-stage>img{display:block;width:100%;height:auto}.hotspot{position:absolute;display:block;z-index:5;border-radius:8px}.hotspot:focus{outline:2px solid #b94f7d;outline-offset:2px}.sr-only{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}.cookie-shot{position:fixed;left:24px;bottom:22px;width:min(460px,calc(100vw - 48px));z-index:30;filter:drop-shadow(0 18px 28px rgba(38,16,25,.18))}.cookie-shot img{width:100%;display:block}.cookie-close{position:absolute;inset:0;border:0;background:transparent;cursor:pointer}.mobile-nav{display:none;padding:12px 16px;background:#fff;border-bottom:1px solid #f5dce6;gap:14px;justify-content:center;flex-wrap:wrap}.mobile-nav a{color:#7a4057;text-decoration:none;font-size:14px}@media(max-width:700px){.mobile-nav{display:flex}.header-stage{display:none}.cookie-shot{left:12px;bottom:12px;width:min(340px,calc(100vw - 24px))}}
</style>
</head><body>
<nav class="mobile-nav" aria-label="Primary"><a href="{{ route('home') }}">Home</a><a href="{{ route('member.listing') }}">Active Members</a><a href="{{ route('packages') }}">Packages</a><a href="{{ route('happy_stories') }}">Happy Stories</a><a href="{{ route('contact_us') }}">Help & Support</a></nav>
<div class="design-shell">
<div class="design-stage header-stage">
<img src="{{ static_asset('assets/hamqadam-design/header.png') }}" alt="Hamqadam navigation">
<a class="hotspot" style="left:3%;top:48%;width:11%;height:38%" href="{{ route('home') }}"><span class="sr-only">Home</span></a>
<a class="hotspot" style="left:27%;top:48%;width:9%;height:38%" href="{{ route('home') }}"><span class="sr-only">Home</span></a>
<a class="hotspot" style="left:36%;top:48%;width:11%;height:38%" href="{{ route('member.listing') }}"><span class="sr-only">Active Members</span></a>
<a class="hotspot" style="left:47%;top:48%;width:9%;height:38%" href="{{ route('packages') }}"><span class="sr-only">Packages</span></a>
<a class="hotspot" style="left:56%;top:48%;width:10%;height:38%" href="{{ route('happy_stories') }}"><span class="sr-only">Happy Stories</span></a>
<a class="hotspot" style="left:66%;top:48%;width:10%;height:38%" href="{{ route('contact_us') }}"><span class="sr-only">Help & Support</span></a>
<a class="hotspot" style="left:82%;top:48%;width:5%;height:38%" href="{{ route('user.login') }}"><span class="sr-only">Login</span></a>
<a class="hotspot" style="left:87%;top:43%;width:8%;height:45%" href="{{ route('user.login') }}"><span class="sr-only">Register now</span></a>
</div>
<div class="design-stage main-stage"><img src="{{ static_asset('assets/hamqadam-design/'.$designImage) }}" alt="{{ $pageTitle ?? 'Hamqadam' }} design">@yield('hotspots')</div>
<div class="design-stage"><img src="{{ static_asset('assets/hamqadam-design/footer.png') }}" alt="Hamqadam footer"></div>
</div>
<div class="cookie-shot" id="cookieShot"><img src="{{ static_asset('assets/hamqadam-design/cookie.png') }}" alt="Cookie and privacy consent"><button class="cookie-close" type="button" aria-label="Accept cookies" onclick="this.parentNode.remove()"></button></div>
</body></html>

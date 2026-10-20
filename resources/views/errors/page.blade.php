@php
    // Keep this page standalone: errors can happen before database-backed
    // settings, login state, layout composers or assets are available.
    $code = (int) ($status ?? 500);
    $messages = [
        400 => ['Bad request', 'We could not understand that request. Please check the link and try again.', 'Review the address or return to the homepage.'],
        401 => ['Sign-in required', 'Please sign in before accessing this page.', 'Your account information remains private.'],
        403 => ['Access restricted', 'You do not have permission to open this page.', 'If you believe this is an error, please contact our support team.'],
        404 => ['We could not find that page', 'The page may have moved, or the link may no longer be available.', 'You can return to the homepage or ask us for help.'],
        405 => ['This action is unavailable', 'The requested action is not supported from this page.', 'Return to the previous screen and try the available options.'],
        408 => ['This request timed out', 'The connection took longer than expected.', 'Please check your connection and try again.'],
        409 => ['This request could not be completed', 'The information may have changed while you were working.', 'Refresh the relevant page before making another change.'],
        410 => ['This page is no longer available', 'The content you requested has been removed.', 'Explore the homepage for current information.'],
        413 => ['Your upload is too large', 'The file exceeds the allowed upload size.', 'Choose a smaller file and submit it again.'],
        419 => ['Your session has expired', 'For your protection, this form or session needs to be refreshed.', 'Go back, reload the form and submit it again.'],
        422 => ['We could not process that request', 'Some information could not be accepted.', 'Please check the information you entered and try again.'],
        423 => ['This feature is temporarily restricted', 'This action is not available for your account at the moment.', 'Please check your account status or contact our support team.'],
        429 => ['Too many requests', 'There have been too many attempts in a short time.', 'Please wait a little before trying again.'],
        500 => ['Something did not go as planned', 'We encountered an unexpected problem while loading this page.', 'Our team can investigate. Please try again later.'],
        502 => ['We could not reach a service', 'One of our services is temporarily not responding.', 'Please return in a moment.'],
        503 => ['We will be right back', 'Hamqadam is temporarily unavailable or undergoing maintenance.', 'Thank you for your patience while we restore service.'],
        504 => ['The service took too long to respond', 'The request timed out while contacting a service.', 'Please try again in a few moments.'],
    ];
    $copy = $messages[$code] ?? ($code >= 500
        ? $messages[500]
        : [ 'We could not open this page', 'This request could not be completed.', 'You can return home or contact our team for assistance.' ]);
    $primaryUrl = url('/');
    $supportUrl = url('/contact-us/page');
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#fff7fa">
  <title>{{ $code }} · {{ $copy[0] }} | Hamqadam</title>
  <style>
    :root{color-scheme:light;--wine:#86153d;--pink:#c1497a;--ink:#342635;--muted:#746778;--line:#f0d9e2}
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0;min-height:100vh;background:radial-gradient(ellipse 55% 55% at 0% 5%,#fbe4ee 0,transparent 100%),radial-gradient(ellipse 50% 65% at 98% 95%,#ffe6ee 0,transparent 100%),#fffafb;color:var(--ink);font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif;line-height:1.55}
    .wrap{width:min(1140px,calc(100% - 40px));margin:auto;min-height:100vh;display:flex;flex-direction:column}
    .topbar{padding:29px 0;display:flex;align-items:center;justify-content:space-between;gap:20px}
    .brand{display:inline-flex;align-items:center;gap:12px;text-decoration:none;color:#35202d}
    .brand-mark{height:46px;width:46px;border:1px solid #f3cadb;border-radius:50%;display:grid;place-items:center;background:#fff0f6;color:#be4d80}
    .brand-name{font-family:Georgia,"Times New Roman",serif;font-weight:700;font-size:29px;line-height:1}
    .brand-tag{display:block;font-size:9px;letter-spacing:.15em;font-weight:700;text-transform:uppercase;color:#ae7c29;margin-top:5px}
    .top-link{color:var(--wine);font-size:13px;font-weight:650;text-decoration:none}
    .content{display:grid;grid-template-columns:minmax(0,1.02fr) minmax(0,.98fr);gap:clamp(30px,5vw,85px);align-items:center;flex:1;padding:55px 0 85px}
    .kicker{display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border:1px solid #f0ccdc;background:#fff0f5;border-radius:999px;color:var(--wine);font-size:12px;font-weight:700;letter-spacing:.075em;text-transform:uppercase}
    .code{font-family:Georgia,"Times New Roman",serif;font-size:clamp(98px,14vw,180px);font-weight:700;letter-spacing:-.09em;line-height:.96;color:var(--wine);margin:24px 0 9px}
    h1{font-family:Georgia,"Times New Roman",serif;font-size:clamp(35px,4.1vw,57px);line-height:1.12;letter-spacing:-.025em;margin:0 0 22px;color:#3e2935}
    .lead{font-size:17px;color:#655767;margin:0 0 12px;max-width:540px;line-height:1.8}
    .hint{font-size:14px;color:var(--muted);max-width:525px;margin:0}
    .buttons{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:34px}
    .btn{min-height:48px;padding:13px 22px;border-radius:11px;border:1px solid var(--line);font:inherit;font-size:14px;font-weight:700;text-decoration:none;display:inline-flex;justify-content:center;align-items:center;gap:9px;cursor:pointer;transition:transform .2s,box-shadow .2s}
    .btn:focus-visible,.top-link:focus-visible{outline:3px solid #e2a8c3;outline-offset:3px}
    .btn:hover{transform:translateY(-2px)}
    .btn-primary{background:linear-gradient(120deg,#86153d,#b33a6c);color:#fff;border-color:#8b1c49;box-shadow:0 12px 24px rgba(129,26,62,.15)}
    .btn-secondary{background:#fff;color:var(--wine);border-color:#e8cad6}
    .illustration{position:relative;min-height:390px;display:grid;place-items:center}
    .illustration::before{content:"";position:absolute;width:min(100%,500px);aspect-ratio:1;background:linear-gradient(135deg,#ffe4ef,#fff6f9);border-radius:45% 55% 55% 45%;transform:rotate(-14deg);box-shadow:0 20px 75px rgba(139,37,79,.06)}
    .illustration::after{content:"";position:absolute;width:68%;aspect-ratio:1;border:1px solid rgba(174,72,114,.18);border-radius:50%;transform:rotate(18deg)}
    .art-card{position:relative;z-index:1;width:min(345px,83%);padding:36px 24px 29px;border-radius:27px;background:#fff;box-shadow:0 25px 80px rgba(113,35,66,.13);border:1px solid #fae8ef;text-align:center}
    .art-icon{display:grid;place-items:center;width:100px;height:100px;border-radius:50%;margin:0 auto 24px;background:linear-gradient(145deg,#ffe5ef,#fff4f8);color:#ab396f}
    .art-line{height:9px;margin:13px auto;border-radius:25px;background:#f7e7ee;width:80%}
    .art-line.small{width:58%}
    .art-heart{color:#b94b78;font:700 17px Georgia,serif;margin-top:24px}
    .ornament{position:absolute;z-index:0;font-size:32px;color:#dca5bd}
    .ornament.one{right:5%;top:16%}.ornament.two{left:6%;bottom:18%}
    .footer{border-top:1px solid var(--line);padding:21px 0 26px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;color:#887584;font-size:12px}
    .footer strong{color:var(--wine)}
    @media(max-width:820px){.content{grid-template-columns:1fr;padding:38px 0 65px;gap:8px}.illustration{min-height:270px;order:-1}.art-card{width:255px;padding:26px 18px}.art-icon{width:70px;height:70px;margin-bottom:18px}.art-icon svg{width:35px;height:35px}.ornament{display:none}.code{font-size:105px}.topbar{padding:22px 0}.brand-name{font-size:25px}}
    @media(max-width:490px){.wrap{width:min(100% - 30px,1140px)}.illustration{min-height:235px}.code{margin-top:18px;font-size:88px}.lead{font-size:15px}.btn{width:100%}.top-link{font-size:12px}.brand-mark{width:40px;height:40px}}
    @media(prefers-reduced-motion:reduce){.btn{transition:none}}
  </style>
</head>
<body>
<div class="wrap">
  <header class="topbar">
    <a class="brand" href="{{ $primaryUrl }}" aria-label="Hamqadam homepage">
      <span class="brand-mark">
        <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12 21s-8.2-5.3-9.7-10.3C.8 5.6 6.3 2.8 10.2 6.2L12 8l1.8-1.8c4-3.4 9.5-.6 7.9 4.5C20.2 15.7 12 21 12 21z"/></svg>
      </span>
      <span><span class="brand-name">Hamqadam</span><span class="brand-tag">Matrimonial Sanctuary</span></span>
    </a>
    <a class="top-link" href="{{ $supportUrl }}">Need assistance? &rarr;</a>
  </header>
  <main class="content" id="main-content">
    <section aria-labelledby="error-heading">
      <span class="kicker"><svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 7v6m0 4h.01"/></svg> Something needs attention</span>
      <div class="code" aria-label="Error {{ $code }}">{{ $code }}</div>
      <h1 id="error-heading">{{ $copy[0] }}</h1>
      <p class="lead">{{ $copy[1] }}</p>
      <p class="hint">{{ $copy[2] }}</p>
      <div class="buttons">
        <a class="btn btn-primary" href="{{ $primaryUrl }}">
          <svg width="17" height="17" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21v-8h6v8"/></svg>
          Return to homepage
        </a>
        <a class="btn btn-secondary" href="{{ $supportUrl }}">Contact support &rarr;</a>
      </div>
    </section>
    <div class="illustration" aria-hidden="true">
      <span class="ornament one">&#10022;</span><span class="ornament two">&#10022;</span>
      <div class="art-card">
        <div class="art-icon">
          <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9.1 20.6c-3.1-2.3-6.6-5.3-6.6-9.3A5.5 5.5 0 0 1 12 7.6a5.5 5.5 0 0 1 9.5 3.7c0 4-3.5 7-6.6 9.3L12 22z"/>
            <path d="M12 3v3M6.6 4.4l1.5 2M17.4 4.4l-1.5 2" />
          </svg>
        </div>
        <div class="art-line"></div><div class="art-line small"></div>
        <div class="art-line"></div>
        <div class="art-heart">We are here to help you reconnect.</div>
      </div>
    </div>
  </main>
  <footer class="footer">
    <span>&copy; {{ date('Y') }} <strong>Hamqadam</strong>. Made with care.</span>
    <span>Error {{ $code }} &nbsp;&middot;&nbsp; Your privacy matters to us.</span>
  </footer>
</div>
</body>
</html>

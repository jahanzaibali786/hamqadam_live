{{-- Accessible member display preferences; no private profile content is cached. --}}
<div class="card border-0 shadow-sm mb-3" id="hq-accessibility-panel" lang="{{ app()->getLocale() }}">
    <div class="card-body py-2 d-flex flex-wrap align-items-center" style="gap:.55rem">
        <span class="fw-600 mr-2"><i class="las la-universal-access" aria-hidden="true"></i> {{ translate('Accessibility') }}</span>
        <label for="hq-font-scale" class="mb-0">{{ translate('Text size') }}</label>
        <select id="hq-font-scale" class="form-control form-control-sm" style="max-width:105px" aria-label="{{ translate('Text size') }}">
            <option value="1">100%</option><option value="1.15">115%</option><option value="1.3">130%</option><option value="1.5">150%</option>
        </select>
        <label class="mb-0"><input type="checkbox" id="hq-high-contrast"> {{ translate('High contrast') }}</label>
        <label class="mb-0"><input type="checkbox" id="hq-reduce-motion"> {{ translate('Reduce motion') }}</label>
        <label class="mb-0"><input type="checkbox" id="hq-color-blind"> {{ translate('Color-blind mode') }}</label>
        <label class="mb-0"><input type="checkbox" id="hq-lite-mode"> {{ translate('Low-bandwidth / Lite mode') }}</label>
        <span class="sr-only" id="hq-accessibility-status" aria-live="polite"></span>
    </div>
</div>
<style>
    .hq-accessible-contrast .hq-member-panel-page {filter: contrast(1.22)}
    .hq-reduce-motion *, .hq-reduce-motion *::before, .hq-reduce-motion *::after {animation-duration:0.01ms!important;transition-duration:0.01ms!important;scroll-behavior:auto!important}
    [dir="rtl"] .hq-member-panel-page {direction:rtl;text-align:right}
    .hq-member-panel-page :focus-visible {outline:3px solid #155eef!important;outline-offset:3px}
    .hq-accessible-contrast .hq-member-panel-page a, .hq-accessible-contrast .hq-member-panel-page button {text-decoration:underline}
    .hq-color-blind .badge, .hq-color-blind .btn {outline:1px solid currentColor;outline-offset:1px}
    .hq-lite-mode video, .hq-lite-mode .aiz-carousel, .hq-lite-mode .slick-slider {animation:none!important}
    @media (prefers-reduced-motion: reduce) {
        .hq-member-panel-page *, .hq-member-panel-page *::before, .hq-member-panel-page *::after {animation-duration:0.01ms!important;transition-duration:0.01ms!important;scroll-behavior:auto!important}
    }
</style>
<script>
(function () {
  const root=document.documentElement, font=document.getElementById('hq-font-scale'), contrast=document.getElementById('hq-high-contrast'), motion=document.getElementById('hq-reduce-motion');
  if (!font || !contrast || !motion) return;
  const lite=document.getElementById('hq-lite-mode'),colorblind=document.getElementById('hq-color-blind');
  const key='hamqadam:a11y:v1';
  let p={font:1,contrast:false,motion:window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches,lite:false,colorblind:false};
  try { p=Object.assign(p,JSON.parse(localStorage.getItem(key)||'{}')); } catch (_) {}
  const apply=()=>{
    const scale=Math.max(1,Math.min(1.5,Number(p.font)||1));
    root.style.fontSize=(16*scale)+'px';
    root.classList.toggle('hq-accessible-contrast',!!p.contrast);
    root.classList.toggle('hq-reduce-motion',!!p.motion);
    root.classList.toggle('hq-color-blind',!!p.colorblind);
    root.classList.toggle('hq-lite-mode',!!p.lite);
    if(lite)lite.checked=!!p.lite;if(colorblind)colorblind.checked=!!p.colorblind;
    if(p.lite){document.querySelectorAll('.hq-member-panel-page img').forEach(el=>{if(!el.hasAttribute('loading'))el.setAttribute('loading','lazy')});document.querySelectorAll('.hq-member-panel-page video').forEach(el=>{el.pause();el.autoplay=false;el.preload='none'});}
    font.value=String(scale);contrast.checked=!!p.contrast;motion.checked=!!p.motion;
  };
  font.addEventListener('change',()=>{p.font=font.value;save()});
  contrast.addEventListener('change',()=>{p.contrast=contrast.checked;save()});
  motion.addEventListener('change',()=>{p.motion=motion.checked;save()});
  if(lite)lite.addEventListener('change',()=>{p.lite=lite.checked;save()});
  if(colorblind)colorblind.addEventListener('change',()=>{p.colorblind=colorblind.checked;save()});
  function save(){try{localStorage.setItem(key,JSON.stringify(p));}catch(_){}apply()}
  apply();
})();
</script>

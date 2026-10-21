(function () {
  'use strict';
  function updateSidebarOffset() {
    var shell = document.querySelector('.hq-member-sticky-layout .hq-member-scroll-shell');
    if (!shell || !window.matchMedia('(min-width: 1200px)').matches) return;
    var header = document.querySelector('.hq-reference-header');
    // Use the full visible header, including the announcement strip.
    var bottom = header ? header.getBoundingClientRect().bottom : 0;
    var offset = Math.max(12, Math.ceil(bottom + 12));
    // A fixed header remains at the same viewport position; a non-fixed
    // header can scroll away. Re-measure rather than freezing its first height.
    shell.style.setProperty('--hq-member-sticky-top', offset + 'px');
  }
  function initialize() {
    var region = document.getElementById('hq-member-side-scroll');
    if (!region || region.dataset.scrollInitialized === '1') return;
    region.dataset.scrollInitialized = '1';
    updateSidebarOffset();
    window.addEventListener('resize', updateSidebarOffset, {passive:true});
    window.addEventListener('scroll', updateSidebarOffset, {passive:true});
    var links = Array.prototype.slice.call(region.querySelectorAll('a.aiz-side-nav-link[href]'));
    var currentUrl = new URL(window.location.href);
    function normalize(url) {
      var path = url.pathname || '/';
      while (path.length > 1 && path.charAt(path.length - 1) === '/') path = path.slice(0, -1);
      return path;
    }
    var currentPath = normalize(currentUrl);
    var active = links.find(function (link) {
      try {
        var url = new URL(link.href, window.location.origin);
        return url.origin === currentUrl.origin && normalize(url) === currentPath &&
          (!url.search || url.search === currentUrl.search);
      } catch (e) { return false; }
    });
    if (!active) {
      active = region.querySelector('a.aiz-side-nav-link.active, a.aiz-side-nav-link[aria-current="page"]');
    }
    if (!active) return;
    active.classList.add('hq-current-nav-item');
    active.setAttribute('aria-current','page');
    var parent = active.parentElement;
    while (parent && parent !== region) {
      if (parent.tagName === 'UL' && parent.classList.contains('level-2')) {
        parent.classList.add('mm-show');
        parent.style.display = 'block';
        var parentItem = parent.parentElement;
        if (parentItem) {
          parentItem.classList.add('mm-active');
          var toggle = parentItem.querySelector(':scope > a.aiz-side-nav-link');
          if (toggle) toggle.setAttribute('aria-expanded','true');
        }
      }
      parent = parent.parentElement;
    }
    function reveal() {
      if (!document.body.contains(active)) return;
      var area = region.getBoundingClientRect();
      var selected = active.getBoundingClientRect();
      if (selected.top < area.top + 12 || selected.bottom > area.bottom - 12) {
        region.scrollTop += (selected.top + selected.height / 2) - (area.top + area.height / 2);
      }
    }
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(reveal);
    });
    window.addEventListener('load', reveal, {once:true});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
  window.addEventListener('pageshow', initialize);
}());

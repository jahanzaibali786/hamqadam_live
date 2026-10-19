(function () {
  'use strict';
  function initialize() {
    var region = document.getElementById('hq-member-side-scroll');
    if (!region || region.dataset.scrollInitialized === '1') return;
    region.dataset.scrollInitialized = '1';
    var links = Array.prototype.slice.call(region.querySelectorAll('a.aiz-side-nav-link[href]'));
    var currentUrl = new URL(window.location.href);
    function normalize(url) {
      var path = url.pathname.replace(/\\/+$/, '') || '/';
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
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
  window.addEventListener('pageshow', initialize);
}());

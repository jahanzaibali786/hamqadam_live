(function(){'use strict';
function active(){var nav=document.querySelector('.aiz-sidebar.left');var menu=document.getElementById('main-menu');if(!nav||!menu)return;
var path=location.pathname.replace(/\/+$/,'')||'/';
var links=Array.from(menu.querySelectorAll('a.aiz-side-nav-link[href]'));
var link=links.find(function(el){try{var u=new URL(el.href,location.href);return u.origin===location.origin&&(u.pathname.replace(/\/+$/,'')||'/')===path;}catch(e){return false;}});
if(!link)link=menu.querySelector('a.aiz-side-nav-link.active, li.mm-active > a.aiz-side-nav-link');
if(!link)return;
link.classList.add('active');link.setAttribute('aria-current','page');
var node=link.parentElement;
while(node&&node!==menu){if(node.tagName==='UL'&&node.classList.contains('level-2')){node.style.display='block';node.classList.add('mm-show');}if(node.tagName==='LI')node.classList.add('mm-active');node=node.parentElement;}
requestAnimationFrame(function(){var a=nav.getBoundingClientRect(),b=link.getBoundingClientRect();if(b.top<a.top+16||b.bottom>a.bottom-16)nav.scrollTop+=(b.top+b.height/2)-(a.top+a.height/2);});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',active);else active();window.addEventListener('pageshow',active);
})();

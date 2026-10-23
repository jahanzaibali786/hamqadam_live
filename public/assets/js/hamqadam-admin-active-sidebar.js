(function () {
'use strict';
function init() {
 const menu=document.querySelector('#main-menu'), nav=document.querySelector('.aiz-sidebar.left');
 if(!nav||!menu)return;
 const current=(location.pathname.replace(/\/+$/,'')||'/');
 const all=[...menu.querySelectorAll('a.aiz-side-nav-link[href]')];
 let link=all.find(el=>{try{const u=new URL(el.href,location.href);return u.origin===location.origin&&(u.pathname.replace(/\/+$/,'')||'/')===current;}catch(e){return false;}});
 if(!link)link=menu.querySelector('a.aiz-side-nav-link.active,li.mm-active > a.aiz-side-nav-link');
 if(!link)return;
 link.classList.add('active');link.setAttribute('aria-current','page');
 for(let p=link.parentElement;p&&p!==menu;p=p.parentElement){
  if(p.tagName==='UL'&&p.classList.contains('level-2')){p.classList.add('mm-show');p.style.visibility='visible';p.style.height='auto';p.style.display='block';}
  if(p.tagName==='LI')p.classList.add('mm-active');
 }
 const move=()=>{const item=link.getBoundingClientRect();let scroller=link.parentElement;while(scroller&&scroller!==document.body){const css=getComputedStyle(scroller);if(/auto|scroll/.test(css.overflowY)&&scroller.scrollHeight>scroller.clientHeight+4)break;scroller=scroller.parentElement;}scroller=scroller&&scroller!==document.body?scroller:nav;const bounds=scroller.getBoundingClientRect();scroller.scrollTop+=(item.top+item.height/2)-(bounds.top+bounds.height/2);};
 requestAnimationFrame(()=>requestAnimationFrame(move));
 window.addEventListener('load',move,{once:true});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
window.addEventListener('pageshow',init);
})();
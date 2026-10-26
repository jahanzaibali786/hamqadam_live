(function(){'use strict';
function boot(){
 const menu=document.querySelector('#main-menu');if(!menu||menu.classList.contains('d-none'))return;
 const norm=p=>(p||'/').replace(/\/+$/,'')||'/';
 const here=norm(location.pathname);
 const links=Array.from(menu.querySelectorAll('a.aiz-side-nav-link[href]')).filter(a=>{try{const u=new URL(a.href,location.href);return u.origin===location.origin&&norm(u.pathname)===here}catch(e){return false}});
 const target=links.find(a=>a.classList.contains('active'))||links[0]||menu.querySelector('a.aiz-side-nav-link.active,li.mm-active > a.aiz-side-nav-link');
 if(!target)return;
 target.classList.add('active');target.setAttribute('aria-current','page');
 for(let p=target.parentElement;p&&p!==menu;p=p.parentElement){if(p.tagName==='LI')p.classList.add('mm-active');if(p.tagName==='UL'){p.classList.add('mm-show');p.removeAttribute('style')}}
 const scroll=()=>{let scroller=target.parentElement;while(scroller&&scroller!==document.body){const st=getComputedStyle(scroller);if(/auto|scroll/.test(st.overflowY)&&scroller.scrollHeight>scroller.clientHeight+3)break;scroller=scroller.parentElement}
 if(!scroller||scroller===document.body)scroller=document.querySelector('.aiz-side-nav-wrap')||document.querySelector('.aiz-sidebar.left');
 if(!scroller)return;
 const item=target.getBoundingClientRect(),frame=scroller.getBoundingClientRect();
 const offset=(item.top+item.height/2)-(frame.top+frame.height/2);
 scroller.scrollTop+=offset;
 };
 requestAnimationFrame(()=>requestAnimationFrame(scroll));
 window.addEventListener('load',scroll,{once:true});
 setTimeout(scroll,300);setTimeout(scroll,850);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
window.addEventListener('pageshow',boot);
})();
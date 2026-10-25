(function(){
'use strict';
let openMenu=null,openButton=null;
function closeMenu(){
 if(!openMenu)return;
 openMenu.hidden=true;openButton?.setAttribute('aria-expanded','false');
 openMenu=null;openButton=null;
}
function reposition(){
 if(!openMenu||!openButton)return;
 const r=openButton.getBoundingClientRect();
 const width=openMenu.offsetWidth, height=openMenu.offsetHeight;
 const spaceBelow=window.innerHeight-r.bottom;
 const top=spaceBelow>=Math.min(height,260)||r.top<height+12?r.bottom+7:Math.max(8,r.top-height-7);
 openMenu.style.top=Math.max(8,Math.min(top,window.innerHeight-height-8))+'px';
 openMenu.style.left=Math.max(8,Math.min(r.right-width,window.innerWidth-width-8))+'px';
}
document.addEventListener('click',function(event){
 const btn=event.target.closest('.hm-match-menu-toggle');
 if(btn){
  event.preventDefault();event.stopPropagation();
  const menu=btn.closest('.hm-match-menu')?.querySelector('.hm-match-menu-panel');
  if(!menu)return;
  if(openMenu===menu){closeMenu();return}
  closeMenu();
  // Body portal prevents overflow, transformed grid tracks, and Popper offsets from shifting the flyout to another card.
  document.body.appendChild(menu);
  menu.hidden=false;openMenu=menu;openButton=btn;
  btn.setAttribute('aria-expanded','true');reposition();
  return;
 }
 if(openMenu&&!event.target.closest('.hm-match-menu-panel'))closeMenu();
});
document.addEventListener('keydown',function(event){if(event.key==='Escape'&&openMenu){const trigger=openButton;closeMenu();trigger?.focus()}});
window.addEventListener('resize',reposition);
document.addEventListener('scroll',reposition,true);
})();

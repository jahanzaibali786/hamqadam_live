/* Public hamburger menu: self-contained, progressively enhanced, and
 * isolated from the member dashboard navigation. */
(function () {
 'use strict';
 const nav=document.getElementById('hq-public-mobile-navigation');
 const button=document.querySelector('.hq-public-menu-toggle');
 if(!nav||!button)return;
 const mobile=window.matchMedia('(max-width: 991.98px)');
 function isOpen(){return button.getAttribute('aria-expanded')==='true';}
 function close(returnFocus){
  if(!isOpen())return;
  nav.hidden=true;
  button.setAttribute('aria-expanded','false');
  button.setAttribute('aria-label','Open navigation menu');
  const icon=button.querySelector('i');if(icon)icon.className='las la-bars';
  if(returnFocus)button.focus();
 }
 function open(){
  if(!mobile.matches)return;
  nav.hidden=false;
  button.setAttribute('aria-expanded','true');
  button.setAttribute('aria-label','Close navigation menu');
  const icon=button.querySelector('i');if(icon)icon.className='las la-times';
 }
 button.addEventListener('click',()=>isOpen()?close(false):open());
 document.addEventListener('keydown',event=>{
  if(event.key==='Escape'&&isOpen()){event.preventDefault();close(true);}
 });
 document.addEventListener('pointerdown',event=>{
  if(isOpen()&&!nav.contains(event.target)&&!button.contains(event.target))close(false);
 });
 nav.addEventListener('click',event=>{
  if(event.target.closest('a'))close(false);
 });
 function onSizeChange(){if(!mobile.matches)close(false);}
 if(mobile.addEventListener)mobile.addEventListener('change',onSizeChange);
 else if(mobile.addListener)mobile.addListener(onSizeChange);
 nav.hidden=true;
 button.setAttribute('aria-expanded','false');
})();

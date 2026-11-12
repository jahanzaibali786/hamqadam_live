/* Stable avatar replacement, including chat rows inserted by Ajax/Echo.
   Capture image errors (they do not bubble) and only modify member avatars,
   never media attachments. The default image cannot loop if it fails. */
(function () {
 'use strict';
 const selector=[
  '.hm-messages-reference .chat-header .avatar img',
  '.hm-messages-reference .chat-user-item .avatar img',
  '.hm-messages-reference .chat-coversation .avatar img',
  '.hm-messages-reference .hm-chat-side-avatar img',
  '.hm-messages-reference .chat-info-wrap .avatar img'
 ].join(',');
 const fallback=document.querySelector('.hm-messages-reference')?.getAttribute('data-chat-avatar-fallback');
 if(!fallback)return;
 function replaceIfBroken(img){
  if(!(img instanceof HTMLImageElement)||!img.matches(selector))return;
  if(img.dataset.chatAvatarFallback==='1')return;
  img.dataset.chatAvatarFallback='1';
  img.src=fallback;
 }
 document.addEventListener('error',function(event){replaceIfBroken(event.target)},true);
 function scan(root){
  if(root instanceof HTMLImageElement) {
   if(root.complete&&root.naturalWidth===0)replaceIfBroken(root);
  }
  root.querySelectorAll?.(selector).forEach(img=>{
   if(img.complete&&img.naturalWidth===0)replaceIfBroken(img);
  });
 }
 const panel=document.querySelector('.hm-messages-reference');
 if(panel) {
  scan(panel);
  new MutationObserver(records=>{
   for(const record of records) {
    if(record.type==='attributes') {
     const img=record.target;
     if(img instanceof HTMLImageElement&&img.dataset.chatAvatarFallback==='1'&&img.src!==fallback)delete img.dataset.chatAvatarFallback;
     if(img.complete&&img.naturalWidth===0)replaceIfBroken(img);
    }else for(const node of record.addedNodes) {
     if(node.nodeType===1)scan(node);
    }
   }
  }).observe(panel,{childList:true,subtree:true,attributes:true,attributeFilter:['src']});
 }
})();

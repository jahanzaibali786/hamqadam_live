/* Web-only receipt, typing and presence layer; interoperates with Laravel Echo app events. */
(function(){
'use strict';
const conf=window.hamqadamWebChatConfig;
if(!conf)return;
const statusRank={sent:0,delivered:1,read:2};
let typingTimer=null,typingDeadline=0,subscribedThread=0,lastTypingAt=0,heartbeat=null;
const qs=(x)=>document.querySelector(x);
function currentThread(){return Number(qs('#chat_thread_id')?.value||0)}
function svg(state){return state==='sent'?'<svg viewBox="0 0 23 17" aria-hidden="true"><path d="M2 9l5 5L17 3"/></svg>':'<svg viewBox="0 0 23 17" aria-hidden="true"><path d="M1 9l5 5L16 3M8 9l5 5L23 3"/></svg>'}
function applyStatus(id,state){const element=qs('.hm-msg-status[data-message-id="'+Number(id)+'"]');if(!element||!(state in statusRank))return;if(statusRank[element.dataset.status] >=statusRank[state])return;element.dataset.status=state;element.title=state==='read'?'Seen':state==='delivered'?'Delivered':'Sent';element.setAttribute('aria-label',element.title);element.innerHTML=svg(state)+'<span class="hm-msg-status-word">'+element.title+'</span>';}
function updateStatuses(event,state){if(Number(event?.thread_id)!==currentThread())return;(event.message_ids||[]).forEach(id=>applyStatus(id,state));}
function showTyping(event){if(Number(event?.thread_id)!==currentThread()||Number(event?.user?.id)===Number(conf.userId))return;const el=qs('#hm-web-typing');if(!el)return;const active=Boolean(event.is_typing);el.hidden=!active;typingDeadline=active?Date.now()+9500:0;const header=qs('#hm-web-presence');if(active&&header){header.textContent='typing…';header.classList.add('is-typing')}else if(header){header.classList.remove('is-typing');refreshPresence()}clearTimeout(typingTimer);if(active)typingTimer=setTimeout(()=>{if(Date.now()>=typingDeadline){el.hidden=true;if(header){header.classList.remove('is-typing');refreshPresence()}}},10000)}
function post(url,payload){if(!url)return Promise.resolve();return fetch(url,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':conf.csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(payload)}).then(r=>{if(!r.ok)throw new Error('Request failed');return r.json()})}
function receiptDelivered(){const id=currentThread();if(id&&document.visibilityState==='visible')post(conf.delivered,{chat_thread_id:id}).catch(()=>{})}
function refreshPresence(){const id=currentThread();if(!id||document.visibilityState!=='visible')return;post(conf.presence,{chat_thread_id:id}).then(data=>{if(id!==currentThread())return;const el=qs('#hm-web-presence');if(!el||el.classList.contains('is-typing'))return;el.textContent=data.online?'Online':data.last_seen?'Last seen '+data.last_seen:'Offline';el.classList.toggle('is-online',Boolean(data.online))}).catch(()=>{})}
function bindThread(){const id=currentThread();if(!id||id===subscribedThread)return;subscribedThread=id;const el=qs('#hm-web-typing');if(el)el.hidden=true;receiptDelivered();refreshPresence();if(!window.Echo)return;/* Bind only the missing status listeners to the existing shell subscription. */ const channel=window.Echo.private('chat-thread.'+id);channel.listen('.message-delivered',e=>updateStatuses(e.data||e,'delivered')).listen('.message-read',e=>updateStatuses(e.data||e,'read')).listen('.typing-indicator',e=>showTyping(e.data||e));}
document.addEventListener('input',e=>{if(e.target?.id!=='message')return;const id=currentThread(),now=Date.now();if(!id||!e.target.value.trim()||now-lastTypingAt<2700)return;lastTypingAt=now;post(conf.typing,{chat_thread_id:id}).catch(()=>{})});
document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible'){receiptDelivered();refreshPresence()}});
document.addEventListener('focusin',e=>{if(e.target?.id==='message')refreshPresence()});
document.addEventListener('click',e=>{if(e.target.closest('.chat-user-item'))setTimeout(()=>{subscribedThread=0;bindThread()},450)});
if(window.jQuery){window.jQuery(document).ajaxComplete((_e,_xhr,settings)=>{if(settings.url?.includes('/single-chat/')||settings.url?.includes('/chat/refresh/')){subscribedThread=0;setTimeout(bindThread,30)};if(settings.url?.includes('/chat-reply'))setTimeout(receiptDelivered,100)});}
const observer=new MutationObserver(()=>{const id=currentThread();if(id!==subscribedThread)bindThread()});const host=qs('#single_chat');if(host)observer.observe(host,{childList:true,subtree:false});
heartbeat=setInterval(refreshPresence,30000);setTimeout(bindThread,80);
})();
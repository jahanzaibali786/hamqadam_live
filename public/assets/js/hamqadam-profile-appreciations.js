(()=>{'use strict';
const shell=document.querySelector('.hm-member-app');if(!shell)return;
let balance=Number(shell.dataset.appreciationBalance||0),cost=Number(shell.dataset.appreciationCost||10),busy=false;
const csrf=shell.dataset.appreciationCsrf;
async function send(url,payload={}){const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(payload)});let data={};try{data=await response.json()}catch{}if(!response.ok)throw Error(data?.errors?.coins?.[0]||data?.errors?.member?.[0]||data?.message||'Action failed');return data}
document.addEventListener('click',async event=>{
 const btn=event.target.closest('[data-like-url],[data-super-url],[data-shortlist-id]');if(!btn||busy||btn.disabled)return;
 if(btn.dataset.shortlistId){
  busy=true;btn.disabled=true;
  const saved=btn.getAttribute('aria-pressed')==='true';
  try{
   const params=new URLSearchParams({_token:csrf,id:btn.dataset.shortlistId});
   const response=await fetch(saved?btn.dataset.shortlistRemoveUrl:btn.dataset.shortlistAddUrl,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,'Accept':'text/html,application/json','Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:params.toString()});
   const result=(await response.text()).trim();
   if(!response.ok||result!=='1')throw Error('Unable to update your shortlist.');
   const newSaved=!saved;
   document.querySelectorAll('[data-shortlist-id="'+btn.dataset.shortlistId+'"]').forEach(control=>{
    control.classList.toggle('is-active',newSaved);control.setAttribute('aria-pressed',String(newSaved));
    control.title=newSaved?'Remove from Shortlist':'Shortlist';
    control.setAttribute('aria-label',(newSaved?'Remove from Shortlist ':'Shortlist ')+(control.closest('[data-member-name]')?.dataset.memberName||'member'));
    const icon=control.querySelector('i');if(icon)icon.className=(newSaved?'las':'lar')+' la-bookmark';
    const label=control.querySelector('span');if(label)label.textContent=newSaved?'Shortlisted':'Shortlist';
   });
  }catch(error){alert(error.message)}finally{busy=false;btn.disabled=false}
  return;
 }
 const name=btn.closest('[data-member-name]')?.dataset.memberName||'this member';
 const superLike=!!btn.dataset.superUrl;
 if(superLike){
  if(balance<cost){if(window.confirm('Super Like costs '+cost+' Coins. Available: '+balance+'. Add Coins?'))location.assign('/custom-coins');return;}
  if(!window.confirm('Super Like '+name+'? Cost: '+cost+' Coins. Available: '+balance+'. Confirm Super Like – '+cost+' Coins'))return;
 }
 busy=true;btn.disabled=true;
 try{
  const data=await send(superLike?btn.dataset.superUrl:btn.dataset.likeUrl,superLike?{quoted_cost:cost}:{});
  if(superLike){balance=data.balance; shell.dataset.appreciationBalance=String(balance);btn.classList.add('is-active');if(btn.querySelector('span'))btn.querySelector('span').textContent='Super Liked';btn.setAttribute('aria-pressed','true');alert(data.created?'Super Like sent successfully.':'Already Super Liked. No additional coins deducted.');}
  else{btn.classList.toggle('is-active',!!data.liked);btn.setAttribute('aria-pressed',String(!!data.liked));if(btn.querySelector('span'))btn.querySelector('span').textContent=data.liked?'Liked':'Like';btn.querySelector('i').className=(data.liked?'las':'lar')+' la-heart';btn.disabled=false;}
 }catch(error){btn.disabled=false;alert(error.message)}
 finally{busy=false}
});
})();
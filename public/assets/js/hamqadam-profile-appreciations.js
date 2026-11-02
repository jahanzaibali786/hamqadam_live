(()=>{'use strict';
const shell=document.querySelector('.hm-member-app');if(!shell)return;
let balance=Number(shell.dataset.appreciationBalance||0),cost=Number(shell.dataset.appreciationCost||10),busy=false;
const csrf=shell.dataset.appreciationCsrf;
async function send(url,payload={}){const response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify(payload)});let data={};try{data=await response.json()}catch{}if(!response.ok)throw Error(data?.errors?.coins?.[0]||data?.errors?.member?.[0]||data?.message||'Action failed');return data}
document.addEventListener('click',async event=>{
 const btn=event.target.closest('[data-like-url],[data-super-url]');if(!btn||busy||btn.disabled)return;
 const name=btn.closest('.hm-profile-appreciations')?.dataset.memberName||'this member';
 const superLike=!!btn.dataset.superUrl;
 if(superLike){
  if(balance<cost){if(window.confirm('Super Like costs '+cost+' Coins. Available: '+balance+'. Add Coins?'))location.assign('/custom-coins');return;}
  if(!window.confirm('Super Like '+name+'? Cost: '+cost+' Coins. Available: '+balance+'. Confirm Super Like – '+cost+' Coins'))return;
 }
 busy=true;btn.disabled=true;
 try{
  const data=await send(superLike?btn.dataset.superUrl:btn.dataset.likeUrl,superLike?{quoted_cost:cost}:{});
  if(superLike){balance=data.balance; shell.dataset.appreciationBalance=String(balance);btn.classList.add('is-active');btn.querySelector('span').textContent='Super Liked';alert(data.created?'Super Like sent successfully.':'Already Super Liked. No additional coins deducted.');}
  else{btn.classList.toggle('is-active',!!data.liked);btn.setAttribute('aria-pressed',String(!!data.liked));btn.querySelector('span').textContent=data.liked?'Liked':'Like';btn.querySelector('i').className=(data.liked?'las':'lar')+' la-heart';btn.disabled=false;}
 }catch(error){btn.disabled=false;alert(error.message)}
 finally{busy=false}
});
})();
(()=>{'use strict';
const root=document.getElementById('hqcms-editor');if(!root)return;
let doc=JSON.parse(document.getElementById('hqcms-initial').textContent);
const schema=JSON.parse(document.getElementById('hqcms-schema').textContent);
const form=document.getElementById('hqcms-form');
const label=k=>k.replace(/_/g,' ').replace(/\b\w/g,x=>x.toUpperCase());
const get=path=>path.reduce((o,k)=>o[k],doc);
const set=(path,v)=>{const parent=get(path.slice(0,-1));parent[path.at(-1)]=v;};
function node(tag,cls,txt){const el=document.createElement(tag);if(cls)el.className=cls;if(txt!==undefined)el.textContent=txt;return el;}
function fields(obj,spec,path,target){Object.keys(spec).forEach(key=>{const value=obj[key]??spec[key],shape=spec[key],p=[...path,key];
 if(Array.isArray(shape)){target.appendChild(repeat(value,shape,p,key));return}
 if(shape!==null&&typeof shape==='object'){const wrap=node('div','hqcms-nested');wrap.appendChild(node('h4','',label(key)));fields(value,shape,p,wrap);target.appendChild(wrap);return}
 const wrap=node('div','hqcms-field');const lab=node('label','',label(key));const id='hqcms-'+p.join('-');lab.htmlFor=id;wrap.appendChild(lab);
 const inp=typeof shape==='number'?node('input',''):String(value).length>110||/description|quote|intro|content|notice|closing|subtitle|bio|title/i.test(key)&&String(value).length>70?node('textarea',''):node('input','');
 inp.id=id;if(inp.tagName==='INPUT')inp.type=typeof shape==='number'?'number':'text'; if(typeof shape==='number'){inp.min='0';inp.max='999999999';inp.step='1'}else inp.maxLength=4000;inp.value=value;inp.addEventListener('input',()=>set(p,typeof shape==='number'?Number(inp.value)||0:inp.value));
 wrap.appendChild(inp);target.appendChild(wrap);
 });}
function repeat(value,shape,path,key){const wrap=node('div','hqcms-nested');const bar=node('div','hqcms-item-controls');const heading=node('h4','',label(key)+' — '+value.length+' items');const add=node('button','hqcms-btn primary','+ Add item');add.type='button';add.onclick=()=>{const list=get(path);if(list.length>=40){alert('Maximum 40 items per section');return}list.push(JSON.parse(JSON.stringify(shape[0])));render()};bar.appendChild(add);wrap.append(heading,bar);const list=node('div','hqcms-repeat');
 value.forEach((item,i)=>{const section=node('div','hqcms-repeat-item');const controls=node('div','hqcms-item-controls');function ctrl(text,action,disabled){const b=node('button','hqcms-btn',text);b.type='button';b.disabled=disabled;b.onclick=action;controls.appendChild(b)}
 ctrl('↑',()=>{const l=get(path);[l[i-1],l[i]]=[l[i],l[i-1]];render()},i===0);ctrl('↓',()=>{const l=get(path);[l[i+1],l[i]]=[l[i],l[i+1]];render()},i===value.length-1);ctrl('Remove',()=>{if(confirm('Remove this item?')){get(path).splice(i,1);render()}},false);
 section.appendChild(controls);if(item!==null&&typeof item==='object'&&!Array.isArray(item)){
 const pick=shape.filter(s=>s&&typeof s==='object'&&!Array.isArray(s)).sort((a,b)=>{
   const score=s=>Object.keys(item).filter(k=>Object.hasOwn(s,k)).length*3-Object.keys(item).filter(k=>!Object.hasOwn(s,k)).length*8;
   return score(b)-score(a);
 })[0]||shape[0];
 fields(item,pick,[...path,i],section);
}else if(Array.isArray(item)){const nested=repeat(item,shape[0],[...path,i],String(i+1));section.appendChild(nested)}else{const inp=node('textarea','');inp.value=String(item);inp.rows=2;inp.addEventListener('input',()=>set([...path,i],inp.value));section.appendChild(inp)}
 list.appendChild(section)});wrap.appendChild(list);return wrap;}
function render(){root.replaceChildren();Object.keys(schema).forEach(key=>{const section=node('section','hqcms-panel');section.dataset.label=label(key).toLowerCase();section.appendChild(node('h3','',label(key)));fields(doc[key],schema[key],[key],section);root.appendChild(section)});filter();}
function filter(){const q=document.getElementById('hqcms-search').value.toLowerCase().trim();root.querySelectorAll('.hqcms-panel').forEach(el=>el.classList.toggle('hqcms-hidden',q&&!el.textContent.toLowerCase().includes(q)&&!el.querySelector('input[value*="'+q.replace(/["\\]/g,'')+'"]')))}
document.getElementById('hqcms-search').addEventListener('input',filter);
form.addEventListener('submit',()=>document.getElementById('hqcms-payload').value=JSON.stringify(doc));
render();
})();
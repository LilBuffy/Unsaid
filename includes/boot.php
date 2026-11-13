<?php
if (!function_exists('e')) { http_response_code(403); exit; }

function boot_head_html() {
    $base = app_base() . 'assets/backgrounds/';
    $lg = e($base . 'bg-7-lg.jpg');
    $sm = e($base . 'bg-7-sm.jpg');
    $css = <<<CSS
html[data-boot="fade"]::before,html[data-boot="intro"]::before{content:"";position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483647;background:#000;pointer-events:none}
html[data-boot="fade"]::before{animation:unsaidBootFade .6s ease forwards}
html[data-boot="intro"]::before{animation:unsaidBootFade .5s ease 9s forwards}
html[data-boot="intro-run"]{overflow:hidden}
@keyframes unsaidBootFade{0%,34%{opacity:1;visibility:visible}100%{opacity:0;visibility:hidden}}
.unsaid-intro{position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483647;overflow:hidden;background:#000;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:all;animation:unsaidIntroRoot 2.9s linear forwards}
.unsaid-intro .ui-photo{position:absolute;top:0;right:0;bottom:0;left:0;background:#000 url("$lg") center/cover no-repeat;filter:grayscale(1) contrast(1.05);opacity:0;animation:unsaidIntroPhoto 2.5s ease forwards}
.unsaid-intro .ui-shade{position:absolute;top:0;right:0;bottom:0;left:0;background:radial-gradient(ellipse at center,rgba(0,0,0,.25) 0,rgba(0,0,0,.85) 100%)}
.unsaid-intro .ui-brand{position:relative;font-family:"Space Grotesk","Inter",Arial,sans-serif;font-weight:700;font-size:clamp(40px,12vw,104px);line-height:1;letter-spacing:.3em;padding-left:.3em;opacity:0;animation:unsaidIntroBrand 2.5s ease forwards}
.unsaid-intro .ui-line{position:relative;margin:18px 0 0;font-family:"Inter",Arial,sans-serif;font-size:clamp(13px,3.4vw,17px);letter-spacing:.08em;color:rgba(255,255,255,.72);opacity:0;animation:unsaidIntroLine 2.5s ease forwards}
@keyframes unsaidIntroRoot{0%,86%{opacity:1;visibility:visible}100%{opacity:0;visibility:hidden}}
@keyframes unsaidIntroPhoto{0%,10%{opacity:0;transform:scale(1.12)}40%{opacity:.5}78%{opacity:.5}100%{opacity:0;transform:scale(1)}}
@keyframes unsaidIntroBrand{0%,24%{opacity:0;letter-spacing:.7em}48%{opacity:1;letter-spacing:.3em}80%{opacity:1}100%{opacity:0}}
@keyframes unsaidIntroLine{0%,50%{opacity:0;transform:translateY(8px)}64%{opacity:1;transform:none}80%{opacity:1}100%{opacity:0}}
@media (max-width:900px){.unsaid-intro .ui-photo{background-image:url("$sm")}}
@media (prefers-reduced-motion:reduce){
html[data-boot="fade"]::before{animation-duration:.3s}
.unsaid-intro{animation-name:unsaidIntroRootRm;animation-duration:1.4s}
.unsaid-intro .ui-photo{animation:none;opacity:.35}
.unsaid-intro .ui-brand{animation-name:unsaidIntroBrandRm;animation-duration:1.4s;letter-spacing:.3em}
.unsaid-intro .ui-line{animation:none;opacity:0}
@keyframes unsaidIntroRootRm{0%,70%{opacity:1;visibility:visible}100%{opacity:0;visibility:hidden}}
@keyframes unsaidIntroBrandRm{0%{opacity:0}35%,65%{opacity:1}100%{opacity:0}}
}
CSS;
    $js = <<<'JS'
(function(){
var d=document,r=d.documentElement,KEY='unsaid:intro:v1',FLAG='unsaid:noreload',mode='',done=false,usable=true;
try{done=window.localStorage.getItem(KEY)==='1';}catch(e){usable=false;}
if(!done){try{done=window.sessionStorage.getItem(KEY)==='1';}catch(e){}}
if(usable&&!done){
var writable=false;
try{window.localStorage.setItem('unsaid:probe','1');window.localStorage.removeItem('unsaid:probe');writable=true;}catch(e){}
if(!writable){try{window.sessionStorage.setItem('unsaid:probe','1');window.sessionStorage.removeItem('unsaid:probe');writable=true;}catch(e){}}
if(!writable)usable=false;
}
var skip=false;
try{if(window.sessionStorage.getItem(FLAG)){window.sessionStorage.removeItem(FLAG);skip=true;}}catch(e){}
var reload=false;
try{var nav=window.performance&&performance.getEntriesByType?performance.getEntriesByType('navigation'):[];
if(nav&&nav.length){reload=nav[0].type==='reload';}else if(window.performance&&performance.navigation){reload=performance.navigation.type===1;}}catch(e){}
if(usable&&!done){mode='intro';}else if(reload&&!skip){mode='fade';}
if(!mode)return;
r.setAttribute('data-boot',mode);
if(mode!=='intro')return;
var rm=false;try{rm=window.matchMedia('(prefers-reduced-motion: reduce)').matches;}catch(e){}
function start(){
var box=d.createElement('div');
box.className='unsaid-intro';
box.setAttribute('aria-hidden','true');
var photo=d.createElement('div');photo.className='ui-photo';
var shade=d.createElement('div');shade.className='ui-shade';
var brand=d.createElement('div');brand.className='ui-brand';brand.textContent='UNSAID';
var line=d.createElement('p');line.className='ui-line';line.textContent="Say what you can't say.";
box.appendChild(photo);box.appendChild(shade);box.appendChild(brand);box.appendChild(line);
d.body.insertBefore(box,d.body.firstChild);
r.setAttribute('data-boot','intro-run');
setTimeout(function(){
var saved=false;
try{window.localStorage.setItem(KEY,'1');saved=window.localStorage.getItem(KEY)==='1';}catch(e){}
if(!saved){try{window.sessionStorage.setItem(KEY,'1');}catch(e){}}
if(box.parentNode)box.parentNode.removeChild(box);
r.removeAttribute('data-boot');
},rm?1400:2900);
}
if(d.readyState==='loading'){d.addEventListener('DOMContentLoaded',start);}else{start();}
})();
JS;
    return '<style>' . $css . '</style>' . "\n" . '<script>' . $js . '</script>' . "\n";
}

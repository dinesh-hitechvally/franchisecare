<?php

namespace App\Services\Push;

/** The script behind the "Push notifications" block of a landing page: it wires every .fc-push card on the page. */
class PagePrompt
{
    /**
     * When the visitor presses the button the browser asks for permission; once they allow it the browser is registered as a device.
     * No email is needed: the device simply joins the company's devices (a campaign can reach it with "All devices").
     */
    public static function script(string $nonce, string $siteKey): string
    {
        $js = <<<'JS'
(function(){
var KEY='__KEY__';
function uid(){try{var u=localStorage.getItem('fcpush-uid');if(!u){u=(window.crypto&&crypto.randomUUID?crypto.randomUUID():Date.now().toString(36)+Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2));localStorage.setItem('fcpush-uid',u)}return u}catch(e){return null}}
function pushable(){return 'serviceWorker' in navigator&&'PushManager' in window&&'Notification' in window}
function unwrap(j){return j&&j.data!==undefined&&j.success!==undefined?j.data:j}
function bytes(s){s=s.replace(/-/g,'+').replace(/_/g,'/');var r=atob(s+'='.repeat((4-s.length%4)%4)),o=new Uint8Array(r.length);for(var i=0;i<r.length;i++)o[i]=r.charCodeAt(i);return o}
function whenActive(reg){return new Promise(function(res){var w=reg.installing||reg.waiting||reg.active;if(!w||w.state==='activated')return res(reg);w.addEventListener('statechange',function(){if(w.state==='activated')res(reg)})})}
function register(){
  return fetch('/api/push/config?key='+encodeURIComponent(KEY)).then(function(r){return r.json()}).then(unwrap).then(function(cfg){
    return navigator.serviceWorker.register('/api/push/worker.js',{scope:'/api/push/'}).then(whenActive).then(function(reg){
      return reg.pushManager.getSubscription().then(function(sub){return sub||reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:bytes(cfg.public_key)})})
    })
  }).then(function(sub){
    return fetch('/api/push/subscribe',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({key:KEY,subscription:sub.toJSON(),page_url:location.href.split('#')[0],device_uid:uid()})}).then(function(r){if(!r.ok)throw new Error('We could not register this browser. Please try again.')})
  })
}
var LATER='fcpush-later',WEEK=7*24*3600*1000;
function dismissed(){try{var t=+localStorage.getItem(LATER);return t&&Date.now()-t<WEEK}catch(e){return false}}
function rememberDismissed(){try{localStorage.setItem(LATER,String(Date.now()))}catch(e){}}
function unregister(){
  return navigator.serviceWorker.getRegistration('/api/push/worker.js').then(function(reg){return reg&&reg.pushManager.getSubscription()}).then(function(sub){
    if(!sub)return;
    return fetch('/api/push/unsubscribe',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({key:KEY,endpoint:sub.endpoint,device_uid:uid()})}).then(function(){return sub.unsubscribe()})
  })
}
function init(box){
  var push=box.classList.contains('fc-push'),x=box.querySelector('.pn-x'),secs=+box.getAttribute('data-secs')||0;
  var btn=box.querySelector('.fc-push-btn'),off=box.querySelector('.fc-push-off'),cb=box.querySelector('.fc-close-btn'),msg=box.querySelector('.fc-push-msg');
  var on=box.getAttribute('data-on')||'Notifications are on for this device.',offDone=box.getAttribute('data-off')||'Notifications are turned off.';
  function say(t,good){msg.textContent=t;msg.className='fc-push-msg '+(good?'ok':'err')}
  function hide(){box.classList.add('pn-hide')}
  function show(el,yes){if(el)el.style.display=yes?'':'none'}
  if(x)x.addEventListener('click',function(){if(push)rememberDismissed();hide()});
  if(cb)cb.addEventListener('click',hide);
  // without a push action the box is only a message (a link or a close button, and it may hide itself); it stays where it was placed in the page
  if(!push){if(secs)setTimeout(hide,secs*1000);return}
  if(dismissed()){hide();return}
  // notifications on: the "allow" button gives way to the "turn off" button; off: the other way round
  function setOn(isOn,text){show(btn,!isOn);show(off,isOn);if(text)say(text,true);else say('',true)}
  function done(){setOn(true,on)}
  if(!pushable()){show(btn,false);show(off,false);say('This browser cannot receive notifications.',false);return}
  if(Notification.permission==='denied'){say('Notifications are blocked for this site. Allow them in your browser settings, then reload the page.',false)}
  else if(Notification.permission==='granted'){
    navigator.serviceWorker.getRegistration('/api/push/worker.js').then(function(reg){return reg&&reg.pushManager.getSubscription()}).then(function(sub){if(sub)done()},function(){})
  }
  if(btn)btn.addEventListener('click',function(){
    btn.disabled=true;say('',true);
    Notification.requestPermission().then(function(p){
      if(p!=='granted')throw new Error(p==='denied'?'Notifications are blocked for this site. Allow them in your browser settings, then reload the page.':'Notifications were not allowed.');
      return register()
    }).then(function(){btn.disabled=false;done()}).catch(function(e){say(e.message||'Something went wrong. Please try again.',false);btn.disabled=false})
  });
  if(off)off.addEventListener('click',function(){
    off.disabled=true;
    unregister().then(function(){off.disabled=false;setOn(false,offDone)}).catch(function(){off.disabled=false;say('We could not turn notifications off. Please try again.',false)})
  })
}
Array.prototype.forEach.call(document.querySelectorAll('.fc-push,.fc-note'),init)
})();
JS;

        return '<script nonce="'.$nonce.'">'.str_replace('__KEY__', htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8'), $js).'</script>';
    }
}

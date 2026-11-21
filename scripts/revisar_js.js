'use strict';
const http=require('http');
const vm=require('vm');
const urls=['/','/login','/registro','/curso?id=1','/crear-curso','/kardex','/ventas','/chat','/reportes','/certificado?id=1','/perfil'];
let bad=0;let checked=0;
function load(path) {
  return new Promise((resolve,reject)=>{
    http.get('http://localhost/Polycraft-main'+path,r=>{
      let body='';r.setEncoding('utf8');
      r.on('data',chunk=>body+=chunk);
      r.on('end',()=>resolve({code:r.statusCode,body}));
    }).on('error',reject);
  });
}
(async()=>{
  for (const url of urls) {
    const r=await load(url);
    const re=/<script\b([^>]*)>([\s\S]*?)<\/script>/gi;
    let match;
    while ((match=re.exec(r.body))!==null) {
      if (/\bsrc\s*=/.test(match[1])||!match[2].trim())continue;
      try{new vm.Script(match[2],{filename:url});checked++;}
      catch(error){bad++;console.error('JS inválido en',url,error.message);}
    }
    if(r.code!==200){bad++;console.error('HTTP',r.code,url);}
  }
  const js=await load('/assets/js/portal-avance3.js');
  try{new vm.Script(js.body,{filename:'portal-avance3.js'});checked++;}
  catch(error){bad++;console.error('JS inválido portal-avance3.js:',error.message);}
  console.log('Validación de scripts:',checked,'válidos,',bad,'errores');
  process.exit(bad?1:0);
})().catch(error=>{console.error(error.message);process.exit(1);});

'use strict';
const fs=require('fs');
const path=require('path');
const collection=path.join(__dirname,'../postman/Polycraft-Avance3.postman_collection.json');
const doc=JSON.parse(fs.readFileSync(collection,'utf8'));
let total=0,errors=[],tests=0;
function recorrer(items,carpeta) {
  for(const item of items||[]) {
    if(item.item){recorrer(item.item,carpeta+'/'+item.name);continue;}
    total++;
    if(!item.request) {errors.push('Sin request: '+carpeta+'/'+item.name);continue;}
    const req=item.request;
    const url=typeof req.url==='string'?req.url:req.url?.raw;
    if(!url||!url.startsWith('{{baseUrl}}/api/')) errors.push('URL faltante o inválida: '+item.name+' '+url);
    if(!['GET','POST','PATCH','PUT','DELETE','OPTIONS'].includes(req.method))errors.push('Método inválido: '+item.name);
    if(item.event?.some(e=>e.listen==='test'&&e.script?.exec?.length))tests++;
    if(/mercado pago/i.test(item.name)&&(/compra|consulta/i.test(item.name))&&JSON.stringify(item).includes('checkout')&&!JSON.stringify(item).includes('skipRequest')) {
      errors.push('Compra MP sin guardia de prueba: '+item.name);
    }
  }
}
recorrer(doc.item,'');
console.log('Colección:',doc.info?.name);
console.log('Solicitudes:',total,'con tests:',tests,'errores:',errors.length);
for(const e of errors) console.error(e);
process.exit(errors.length?1:0);

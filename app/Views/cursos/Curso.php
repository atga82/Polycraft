<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Curso | Polycraft</title>
<link rel="stylesheet" href="<?= e($base) ?>/assets/css/stylemenu.css">
<style>.pc-wrap{max-width:1040px;margin:22px auto;padding:18px}.pc-panel{padding:24px;background:white;border-radius:12px;margin:18px 0}.pc-panel h2{margin-top:0}.pc-lesson{padding:18px;border-bottom:1px solid #ddd}.pc-lesson video{width:100%;max-width:700px}.pc-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:12px 0}.pc-actions button,.pc-actions select,.pc-actions input{padding:9px}.pc-status{padding:8px;color:#183c60}.pc-review{border-bottom:1px solid #eee;padding:12px 0}.pc-review strong{display:block}.pc-review p{white-space:pre-wrap}.pc-muted{color:#64748b}</style>
</head><body>
<nav class="navbar"><div class="navbar-left"><a class="logo" href="<?= e($base) ?>/">Polycraft</a>
<a class="nav-link" href="<?= e($base) ?>/">Catálogo</a>
<a class="nav-link" href="<?= e($base) ?>/kardex">Kardex</a>
<a class="nav-link" href="<?= e($base) ?>/crear-curso">Crear curso</a>
<a class="nav-link" href="<?= e($base) ?>/chat">Chat</a>
</div><div class="navbar-right"><?php include BASE_PATH . '/app/Views/partials/sesion.php'; ?></div></nav>
<main class="pc-wrap">
<a href="<?= e($base) ?>/">← Volver al catálogo</a>
<p class="pc-status" id="curso-estado" role="status">Cargando curso...</p>
<div class="pc-panel" id="curso-panel" hidden>
<h1 id="curso-titulo"></h1><p id="curso-instructor"></p><p id="curso-categoria"></p>
<p id="curso-descripcion"></p><strong id="curso-precio"></strong>
<p id="curso-promedio"></p>
<div class="pc-actions" id="curso-opciones"></div>
<p class="pc-status" id="compra-estado" role="status"></p>
</div>
<section class="pc-panel" id="curso-gestion" hidden>
<h2>Administrar este curso</h2>
<p>Actualiza la información o da de baja el curso sin eliminar ventas ni inscripciones previas.</p>
<form id="curso-edicion">
<div class="pc-actions"><label>Título <input name="titulo" required maxlength="150"></label>
<label>Precio (MXN) <input name="precio" required type="number" min="0" step="0.01"></label></div>
<label>Descripción <textarea name="descripcion" required maxlength="10000" rows="3" style="width:100%"></textarea></label>
<div class="pc-actions"><button class="btn btn-filled">Guardar cambios</button>
<button type="button" id="curso-baja" class="btn btn-outline">Dar de baja</button>
<button type="button" id="curso-reactivar" class="btn btn-outline" hidden>Reactivar</button></div>
</form>
<h3>Agregar nivel después de publicar</h3>
<form id="curso-nuevo-nivel">
<div class="pc-actions">
<label>Título <input name="titulo" required maxlength="150"></label>
<label>Video (URL HTTP/HTTPS) <input name="video_url" type="url" required></label>
<label>Precio <input name="precio" type="number" min="0" step="0.01" value="0"></label>
<button class="btn btn-filled">Agregar nivel</button></div>
</form>
<p id="gestion-estado" role="status"></p>
</section>
<section class="pc-panel"><h2>Contenido y niveles</h2><p id="niveles-aviso" class="pc-muted"></p><div id="niveles-lista"></div></section>
<section class="pc-panel"><h2>Comentarios y valoraciones</h2><p>Las reseñas son de estudiantes que terminaron el curso; las que retire un administrador aparecen con una leyenda.</p>
<div id="curso-comentarios"></div>
<form id="curso-resena" hidden>
<label>Estrellas <select id="resena-estrellas"><option value="5">5 ★</option><option value="4">4 ★</option><option value="3">3 ★</option><option value="2">2 ★</option><option value="1">1 ★</option></select></label>
<label>Tu reseña <textarea id="resena-texto" maxlength="2000" rows="3" required></textarea></label>
<button class="btn btn-filled">Publicar reseña</button></form>
<p class="pc-status" id="resena-estado" role="status"></p></section>
</main>
<script>
(() => {
const base=<?= json_encode($base,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const rol=<?= json_encode($usuario['rol']??'',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const usuarioId=<?= (int)($usuario['id']??0) ?>;
const $=id=>document.getElementById(id);
const cursoId=new URLSearchParams(location.search).get('id');
const money=v=>Number(v)===0?'Gratuito':new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(v));
// Los archivos del proyecto son relativos a /Polycraft-main, no a la raíz del servidor.
const archivoUrl=url=>String(url||'').startsWith('/uploads/')?base+url:String(url||'');
let curso=null;
async function api(ruta,metodo='GET',datos=null) {
    const opciones={method:metodo,credentials:'same-origin',headers:{Accept:'application/json'}};
    if(datos!==null){opciones.headers['Content-Type']='application/json';opciones.body=JSON.stringify(datos);}
    const response=await fetch(base+ruta,opciones);
    const result=response.status===204?{ok:true,data:null}:await response.json();
    if(!response.ok||!result.ok)throw new Error(result.error?.mensaje||('Error HTTP '+response.status));
    return result.data;
}
function btn(texto,accion){
    const b=document.createElement('button');b.className='btn btn-filled';b.type='button';b.textContent=texto;
    b.onclick=async()=>{b.disabled=true;try{await accion();}catch(e){$('compra-estado').textContent=e.message;}finally{b.disabled=false;}};
    return b;
}
function nodo(tag,txt){const n=document.createElement(tag);if(txt!==undefined)n.textContent=String(txt);return n;}
async function comentarios(){
    if(!curso)return;
    try{
        const data=await api('/api/cursos/'+cursoId+'/comentarios');
        $('curso-comentarios').replaceChildren();
        data.forEach(c=>{
            const caja=nodo('div');caja.className='pc-review';
            caja.append(nodo('strong',c.autor+' · '+(c.calificacion===null?'Moderado':c.calificacion+' / 5 ★')));
            caja.append(nodo('p',c.comentario));caja.append(nodo('small',c.fecha_creacion));
            $('curso-comentarios').append(caja);
        });
        if(!data.length)$('curso-comentarios').textContent='Todavía no hay reseñas.';
    }catch(e){$('resena-estado').textContent=e.message;}
}
async function verificarPago() {
    const id=sessionStorage.getItem('polycraft-compra-'+cursoId);
    if(!id)return;
    try{
        const d=await api('/api/compras/'+id);
        $('compra-estado').textContent='Estado del pago: '+d.estado+'.';
        if(d.estado==='aprobada'){sessionStorage.removeItem('polycraft-compra-'+cursoId);await cargar();}
    }catch(e){$('compra-estado').textContent='No se pudo verificar el pago: '+e.message;}
}
async function iniciarPago(metodo) {
    $('compra-estado').textContent='Preparando la compra...';
    const d=await api('/api/compras','POST',{curso_id:Number(cursoId),metodo_pago:metodo});
    if(metodo==='transferencia_demo'){
        $('compra-estado').textContent='Compra aprobada en modo demostración: no se transfirió dinero.';
        await cargar();return;
    }
    if(!/^https:\/\//.test(d.checkout_url||''))throw new Error('Mercado Pago no devolvió checkout válido.');
    sessionStorage.setItem('polycraft-compra-'+cursoId,d.id_compra);
    location.assign(d.checkout_url);
}
async function cargar(){
    if(!/^[1-9]\d*$/.test(cursoId||'')){$('curso-estado').textContent='El enlace del curso no es válido.';return;}
    if(!rol){$('curso-estado').textContent='Inicia sesión o regístrate para consultar este curso.';return;}
    try{
        curso=await api('/api/cursos/'+cursoId);
        $('curso-panel').hidden=false;
        $('curso-titulo').textContent=curso.titulo;
        $('curso-instructor').textContent='Instructor: '+curso.instructor;
        $('curso-categoria').textContent='Categorías: '+(curso.categorias||'No disponible');
        $('curso-descripcion').textContent=curso.descripcion;
        $('curso-precio').textContent=money(curso.precio);
        $('curso-promedio').textContent='Calificación promedio: '+Number(curso.promedio_calificacion||0).toFixed(1)+' / 5';
        const puedeGestionar=rol==='administrador'||(rol==='instructor'&&Number(curso.instructor_id)===usuarioId);
        $('curso-gestion').hidden=!puedeGestionar;
        if(puedeGestionar){
            const form=$('curso-edicion');
            form.elements.titulo.value=curso.titulo;
            form.elements.descripcion.value=curso.descripcion;
            form.elements.precio.value=curso.precio;
            $('curso-baja').hidden=!curso.activo;
            $('curso-reactivar').hidden=Boolean(curso.activo);
        }
        $('curso-estado').textContent='';
        const ops=$('curso-opciones');ops.replaceChildren();
        if(rol==='estudiante'&&!curso.acceso_contenido){
            if(Number(curso.precio)===0){
                ops.append(btn('Inscribirme gratis',async()=>{
                    await api('/api/inscripciones','POST',{curso_id:Number(cursoId)});
                    $('compra-estado').textContent='Inscripción gratuita registrada.';await cargar();
                }));
            }else{
                const sel=document.createElement('select');sel.id='medio-pago';
                [{v:'transferencia_demo',t:'Transferencia (simulación sin dinero)'},{v:'mercado_pago',t:'Mercado Pago — solo pruebas'}]
                    .forEach(x=>{const o=document.createElement('option');o.value=x.v;o.textContent=x.t;sel.append(o);});
                ops.append(sel,btn('Comprar curso',()=>iniciarPago(sel.value)));
            }
        }else if(curso.acceso_contenido&&rol==='estudiante'){
            ops.append(nodo('strong','Ya tienes acceso a los materiales del curso.'));
        }
        if(sessionStorage.getItem('polycraft-compra-'+cursoId))ops.append(btn('Verificar mi pago en Mercado Pago',verificarPago));
        $('niveles-lista').replaceChildren();
        const niveles=curso.niveles||[];
        $('niveles-aviso').textContent=curso.acceso_contenido?'Puedes revisar y completar cada nivel.':
            'Puedes ver gratis los niveles de muestra. Inscríbete o compra para desbloquear el resto.';
        niveles.forEach(n=>{
            const caja=nodo('article');caja.className='pc-lesson';
            caja.append(nodo('h3','Nivel '+n.numero+': '+n.titulo));
            caja.append(nodo('p',n.descripcion||''));caja.append(nodo('p','Costo del nivel: '+money(n.precio)));
            if(curso.acceso_contenido||Number(n.precio)===0){
                if(n.contenido)caja.append(nodo('p',n.contenido));
                const enlace=nodo('a','Ver video del nivel');
                enlace.href=archivoUrl(n.video_url);enlace.target='_blank';enlace.rel='noopener noreferrer';
                caja.append(enlace);
                if(/\.(mp4|webm)(\?.*)?$/i.test(n.video_url||'')){
                    const v=document.createElement('video');v.controls=true;v.preload='metadata';v.src=archivoUrl(n.video_url);caja.append(v);
                }
                (n.recursos||[]).forEach(r=>{
                    const a=nodo('a','Recurso '+r.tipo+': '+r.titulo);a.href=archivoUrl(r.url);a.target='_blank';
                    a.rel='noopener noreferrer';const d=nodo('div');d.append(a);caja.append(d);
                });
                if(puedeGestionar){
                    caja.append(btn('Editar nivel',async()=>{
                        const titulo=prompt('Título del nivel:',n.titulo);
                        if(titulo===null)return;
                        const video=prompt('URL del video:',n.video_url);
                        if(video===null)return;
                        const precio=prompt('Precio del nivel (MXN):',n.precio);
                        if(precio===null)return;
                        await api('/api/niveles/'+n.id,'PATCH',{titulo:titulo.trim(),video_url:video.trim(),precio:Number(precio)});
                        $('gestion-estado').textContent='Nivel actualizado.';await cargar();
                    }));
                    caja.append(btn('Adjuntar recurso',async()=>{
                        const tipo=prompt('Tipo de recurso: pdf, imagen o enlace','enlace');
                        if(tipo===null)return;
                        const titulo=prompt('Nombre del recurso','Material de apoyo');
                        if(titulo===null)return;
                        const url=prompt('URL http(s) del recurso o ruta /uploads/courses/...','https://');
                        if(url===null)return;
                        await api('/api/niveles/'+n.id+'/recursos','POST',{tipo,titulo,url});
                        $('gestion-estado').textContent='Recurso agregado.';await cargar();
                    }));
                    caja.append(btn('Eliminar nivel',async()=>{
                        if(!confirm('¿Eliminar este nivel? No se puede si ya hay estudiantes inscritos.'))return;
                        await api('/api/niveles/'+n.id,'DELETE');
                        $('gestion-estado').textContent='Nivel eliminado.';await cargar();
                    }));
                }
                if(rol==='estudiante'&&curso.acceso_contenido){
                    caja.append(btn('Marcar nivel completado',async()=>{
                        const res=await api('/api/niveles/'+n.id+'/completar','POST',{});
                        $('compra-estado').textContent='Avance: '+res.niveles_completados+' de '+res.niveles_totales+'.';
                        if(res.certificado){
                            const a=nodo('a','Descargar diploma');a.href=base+'/certificado?id='+res.certificado.id;
                            $('curso-opciones').append(a);
                            $('curso-resena').hidden=false;
                        }
                    }));
                }
            }
            $('niveles-lista').append(caja);
        });
        if(!niveles.length)$('niveles-aviso').textContent='El instructor aún no ha publicado niveles.';
        $('curso-resena').hidden=!(rol==='estudiante'&&curso.acceso_contenido);
        await comentarios();
    }catch(e){$('curso-estado').textContent='No se pudo cargar el curso: '+e.message;}
}
$('curso-edicion').addEventListener('submit',async e=>{
    e.preventDefault();const f=e.currentTarget;
    try{
        await api('/api/cursos/'+cursoId,'PATCH',{titulo:f.elements.titulo.value.trim(),descripcion:f.elements.descripcion.value.trim(),precio:Number(f.elements.precio.value)});
        $('gestion-estado').textContent='Curso actualizado correctamente.';await cargar();
    }catch(error){$('gestion-estado').textContent=error.message;}
});
for(const [id,activo] of [['curso-baja',false],['curso-reactivar',true]]){
    $(id).addEventListener('click',async()=>{
        if(!confirm(activo?'¿Reactivar el curso?':'¿Dar de baja el curso del catálogo? Se conserva el historial.'))return;
        try{await api('/api/cursos/'+cursoId,'PATCH',{activo});$('gestion-estado').textContent='Estado actualizado.';await cargar();}
        catch(error){$('gestion-estado').textContent=error.message;}
    });
}
$('curso-nuevo-nivel').addEventListener('submit',async e=>{
    e.preventDefault();const f=e.currentTarget;
    try{
        await api('/api/cursos/'+cursoId+'/niveles','POST',{titulo:f.elements.titulo.value.trim(),video_url:f.elements.video_url.value.trim(),precio:Number(f.elements.precio.value),descripcion:''});
        $('gestion-estado').textContent='Nivel agregado.';f.reset();await cargar();
    }catch(error){$('gestion-estado').textContent=error.message;}
});
$('curso-resena').addEventListener('submit',async e=>{
    e.preventDefault();
    try{
        await api('/api/cursos/'+cursoId+'/comentarios','POST',{
            calificacion:Number($('resena-estrellas').value),comentario:$('resena-texto').value.trim()
        });
        $('resena-estado').textContent='¡Gracias! Tu reseña quedó publicada.';
        $('resena-texto').value='';await cargar();
    }catch(err){$('resena-estado').textContent=err.message;}
});
cargar().then(verificarPago);
})();
</script></body></html>

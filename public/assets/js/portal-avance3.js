/* Polycraft - interfaces del avance final. Todas las operaciones usan fetch/Promise. */
(() => {
'use strict';
const base=window.POLY_BASE||'';
const rol=window.POLY_ROL||'';
const $=id=>document.getElementById(id);
const pesos=v=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(v)||0);
const fecha=v=>{
    if (!v) return '—';
    const d=new Date(String(v).replace(' ','T'));
    return isNaN(d.valueOf()) ? String(v) : d.toLocaleDateString('es-MX',{day:'2-digit',month:'short',year:'numeric'});
};
const fechaHora=v=>{
    if(!v)return '—';
    const d=new Date(String(v).replace(' ','T'));
    return isNaN(d.valueOf())?String(v):d.toLocaleString('es-MX',{
        day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:false
    });
};
async function api(path,method='GET',body=null) {
    const options={method,credentials:'same-origin',headers:{'Accept':'application/json'}};
    if (body!==null) {
        options.headers['Content-Type']='application/json';
        options.body=JSON.stringify(body);
    }
    const resp=await fetch(base+path,options);
    const j=resp.status===204?{ok:true,data:null}:await resp.json();
    if (!resp.ok||j.ok!==true) throw new Error(j.error?.mensaje||('Error HTTP '+resp.status));
    return j.data;
}
function cell(value) {
    const t=document.createElement('td');
    if (value instanceof Node) t.append(value);
    else t.textContent=value===null||value===undefined?'—':String(value);
    return t;
}
function row(body,values) {
    const tr=document.createElement('tr');
    values.forEach(v=>tr.append(cell(v)));
    body.append(tr);
}
function button(label,callback) {
    const b=document.createElement('button');
    b.type='button';b.textContent=label;b.className='btn btn-outline pc-action';
    b.addEventListener('click',async()=>{b.disabled=true;try{await callback();}catch(e){alert(e.message);}finally{b.disabled=false;}});
    return b;
}
function link(label,url) {
    const a=document.createElement('a');a.textContent=label;a.href=base+url;a.className='btn btn-outline pc-action';return a;
}
function params(form) {
    const search=new URLSearchParams();
    new FormData(form).forEach((v,k)=>{if(v!=='')search.set(k,String(v));});
    return search.toString();
}
async function categoriasEn(select) {
    const data=await api('/api/categorias');
    select.querySelectorAll('option:not(:first-child)').forEach(n=>n.remove());
    data.forEach(c=>{const o=document.createElement('option');o.value=c.id;o.textContent=c.nombre;select.append(o);});
}
function errorEn(id,e){if ($(id)) $(id).textContent=e.message||String(e);}

async function kardex() {
    const form=$('kardex-filtros'),tbody=$('kardex-tabla');
    if(rol!=='estudiante') { $('kardex-estado').textContent='Inicia sesión como estudiante para consultar tu Kardex.';return; }
    try {await categoriasEn(form.elements.categoria);}catch(e){errorEn('kardex-estado',e);}
    const cargar=async()=>{
        $('kardex-estado').textContent='Cargando inscripciones...';
        try{
            const data=await api('/api/inscripciones?'+params(form));
            tbody.replaceChildren();
            data.forEach(i=>{
                const actions=document.createElement('div');
                actions.append(link('Ver curso','/curso?id='+i.curso_id));
                if(i.certificado_id) actions.append(link('Diploma','/certificado?id='+i.certificado_id));
                row(tbody,[i.titulo,fecha(i.fecha_inscripcion),fecha(i.ultimo_acceso),
                    fecha(i.fecha_terminacion),i.completados+' / '+i.total_niveles+' ('+i.porcentaje+'%)',actions]);
            });
            $('kardex-estado').textContent=data.length?data.length+' curso(s) en tu Kardex.':'Aún no tienes inscripciones.';
        }catch(e){errorEn('kardex-estado',e);}
    };
    form.addEventListener('submit',e=>{e.preventDefault();cargar();});
    cargar();
}

async function ventas() {
    const form=$('ventas-filtros'),tbody=$('ventas-tabla');
    if(rol!=='instructor'){$('ventas-estado').textContent='Esta vista requiere una sesión de instructor.';return;}
    try{await categoriasEn(form.elements.categoria);}catch(e){errorEn('ventas-estado',e);}
    const detalle=async(id)=>{
        $('ventas-detalle-curso').textContent='Cargando alumnos...';
        try{
            const d=await api('/api/ventas/'+id);
            $('ventas-detalle-tabla').replaceChildren();
            d.alumnos.forEach(a=>row($('ventas-detalle-tabla'),[
                a.alumno,fecha(a.fecha_inscripcion),
                a.niveles_completados+' / '+a.niveles_totales,
                pesos(a.pagado),a.formas_pago
            ]));
            $('ventas-detalle-curso').textContent=d.curso.titulo+' — '+d.alumnos.length+' alumno(s)';
            $('ventas-detalle-total').textContent='Ingresos del curso: '+pesos(d.total_ingresos);
        }catch(e){errorEn('ventas-detalle-curso',e);}
    };
    const cargar=async()=>{
        $('ventas-estado').textContent='Consultando ventas...';
        try{
            const d=await api('/api/ventas?'+params(form));
            tbody.replaceChildren();
            d.cursos.forEach(c=>row(tbody,[c.titulo,c.alumnos,c.niveles_promedio,
                pesos(c.ingresos),button('Ver alumnos',()=>detalle(c.id))]));
            $('ventas-total').textContent='Ingresos totales: '+pesos(d.total_ingresos);
            const forma=$('ventas-formas');forma.replaceChildren();
            d.por_forma_pago.forEach(f=>{
                const p=document.createElement('p');p.textContent=f.forma_pago+': '+pesos(f.total);forma.append(p);
            });
            $('ventas-estado').textContent=d.cursos.length+' curso(s) en el reporte.';
        }catch(e){errorEn('ventas-estado',e);}
    };
    form.addEventListener('submit',e=>{e.preventDefault();cargar();});cargar();
}

async function admin() {
    if(rol!=='administrador'){$('admin-estado').textContent='Inicia sesión con una cuenta administradora.';return;}
    const aviso=s=>{$('admin-estado').textContent=s;};
    const reporte=async()=>{
        try {
            const tipo=$('reporte-filtros').elements.tipo.value;
            const d=await api('/api/reportes/usuarios?tipo='+tipo);
            const cabeceras=tipo==='instructor'
                ?['Usuario','Nombre','Fecha ingreso','Cursos ofrecidos','Ganancias']
                :['Usuario','Nombre','Fecha ingreso','Cursos inscritos','% terminados'];
            $('reporte-head').replaceChildren();
            const tr=document.createElement('tr');
            cabeceras.forEach(x=>{const th=document.createElement('th');th.textContent=x;tr.append(th);});
            $('reporte-head').append(tr);$('reporte-tabla').replaceChildren();
            d.usuarios.forEach(u=>row($('reporte-tabla'),[u.email,u.nombre,fecha(u.fecha_registro),
                tipo==='instructor'?u.cursos_ofrecidos:u.cursos_inscritos,
                tipo==='instructor'?pesos(u.ganancias):u.porcentaje_terminados+'%']));
            aviso('Reporte actualizado.');
        } catch(e){aviso(e.message);}
    };
    $('reporte-filtros').addEventListener('submit',e=>{e.preventDefault();reporte();});

    const cf=$('categoria-form'),cb=$('categoria-tabla');
    const cargarCategorias=async()=>{
        try{
            const data=await api('/api/categorias');cb.replaceChildren();
            data.forEach(c=>{
                const actions=document.createElement('div');
                actions.append(button('Editar',()=>{
                    cf.elements.id.value=c.id;
                    cf.elements.nombre.value=c.nombre;
                    cf.elements.descripcion.value=c.descripcion||'';
                    cf.elements.nombre.focus();
                }));
                actions.append(button('Eliminar',async()=>{
                    if(!confirm('¿Eliminar la categoría "'+c.nombre+'"? No se borrarán cursos.'))return;
                    await api('/api/categorias/'+c.id,'DELETE');
                    aviso('Categoría eliminada.');await cargarCategorias();
                }));
                row(cb,[c.nombre,c.descripcion||'',c.total_cursos,actions]);
            });
        }catch(e){aviso(e.message);}
    };
    $('categoria-cancelar').addEventListener('click',()=>cf.reset());
    cf.addEventListener('submit',async e=>{
        e.preventDefault();
        try{
            const id=cf.elements.id.value;
            const datos={nombre:cf.elements.nombre.value.trim(),descripcion:cf.elements.descripcion.value.trim()};
            await api(id?'/api/categorias/'+id:'/api/categorias',id?'PATCH':'POST',datos);
            cf.reset();aviso('Categoría guardada correctamente.');await cargarCategorias();
        }catch(err){aviso(err.message);}
    });

    const cargarUsuarios=async()=>{
        try{
            const users=await api('/api/usuarios?limite=100');
            $('usuarios-tabla').replaceChildren();
            users.forEach(u=>{
                const btn=button(u.activo?'Bloquear':'Desbloquear',async()=>{
                    if(!confirm('¿'+(u.activo?'Bloquear':'Desbloquear')+' a '+u.nombre_completo+'?'))return;
                    await api('/api/usuarios/'+u.id+'/estado','PATCH',{activo:!u.activo});
                    aviso('Estado de usuario actualizado.');await cargarUsuarios();
                });
                row($('usuarios-tabla'),[u.nombre_completo,u.email,u.rol,u.activo?'Activo':'Bloqueado',btn]);
            });
        }catch(e){aviso(e.message);}
    };
    const cursos=$('moderacion-cursos');
    const cargarCursos=async()=>{
        try{
            const d=await api('/api/cursos');
            d.cursos.forEach(c=>{const op=document.createElement('option');op.value=c.id;op.textContent=c.titulo;cursos.append(op);});
        }catch(e){aviso(e.message);}
    };
    const cargarComentarios=async()=>{
        if(!cursos.value)return;
        try{
            const datos=await api('/api/cursos/'+cursos.value+'/comentarios');
            $('moderacion-tabla').replaceChildren();
            datos.forEach(c=>{
                const btn=c.eliminado?'Eliminado':button('Retirar',async()=>{
                    const causa=prompt('Indica la causa de eliminación (mínimo 5 caracteres):');
                    if(causa===null)return;
                    await api('/api/comentarios/'+c.id,'DELETE',{causa});
                    aviso('Comentario retirado y motivo registrado.');await cargarComentarios();
                });
                row($('moderacion-tabla'),[c.autor,c.calificacion||'—',c.comentario,fecha(c.fecha_creacion),btn]);
            });
        }catch(e){aviso(e.message);}
    };
    $('moderacion-form').addEventListener('submit',e=>{e.preventDefault();cargarComentarios();});
    await Promise.all([reporte(),cargarCategorias(),cargarUsuarios(),cargarCursos()]);
}

async function chat() {
    if(!rol){$('chat-estado').textContent='Inicia sesión para acceder a tus mensajes.';return;}
    const select=$('chat-contacto'),contenedor=$('chat-mensajes');
    let actual=null;
    const cargar=async()=>{
        if(!actual)return;
        try{
            const mensajes=await api('/api/mensajes?con='+actual);
            contenedor.replaceChildren();
            mensajes.forEach(m=>{
                const p=document.createElement('p');p.style.padding='10px';p.style.borderBottom='1px solid #ddd';
                const imagen=document.createElement('img');
                imagen.src=base+'/uploads/avatars/'+encodeURIComponent(m.avatar||'default.png');
                imagen.alt='Foto de '+m.remitente;
                imagen.width=34;imagen.height=34;
                imagen.style.cssText='object-fit:cover;border-radius:50%;vertical-align:middle;margin-right:10px';
                const nombre=document.createElement('strong');nombre.textContent=m.remitente+' · '+fechaHora(m.fecha_envio);
                const texto=document.createElement('div');texto.textContent=m.contenido;
                p.append(imagen,nombre,texto);contenedor.append(p);
            });
            $('chat-estado').textContent=mensajes.length+' mensaje(s) en la conversación.';
            contenedor.scrollTop=contenedor.scrollHeight;
        }catch(e){errorEn('chat-estado',e);}
    };
    try{
        const lista=await api('/api/contactos');
        lista.forEach(u=>{const op=document.createElement('option');op.value=u.id;op.textContent=u.nombre_completo;select.append(op);});
    }catch(e){errorEn('chat-estado',e);}
    $('chat-selector').addEventListener('submit',e=>{
        e.preventDefault();actual=Number(select.value)||null;cargar();
    });
    $('chat-envio').addEventListener('submit',async e=>{
        e.preventDefault();
        if(!actual){$('chat-estado').textContent='Primero elige con quién conversar.';return;}
        const texto=$('chat-texto').value.trim();
        if(!texto)return;
        try{
            await api('/api/mensajes','POST',{destinatario_id:actual,contenido:texto});
            $('chat-texto').value='';await cargar();
        }catch(err){errorEn('chat-estado',err);}
    });
}

async function certificado() {
    const id=new URLSearchParams(location.search).get('id');
    if(!id||!/^[1-9]\d*$/.test(id)){$('d-alumno').textContent='Certificado no válido';return;}
    try {
        const d=await api('/api/certificados/'+id);
        $('d-alumno').textContent=d.alumno;
        $('d-curso').textContent=d.curso;
        $('d-fecha').textContent=fecha(d.fecha_terminacion);
        $('d-codigo').textContent=d.codigo;
        $('d-instructor').textContent='Certifica: '+d.instructor;
    } catch(e){$('d-alumno').textContent='No se pudo abrir: '+e.message;}
}
if($('pc-kardex'))kardex();
if($('pc-ventas'))ventas();
if($('pc-admin'))admin();
if($('pc-chat'))chat();
if($('d-alumno'))certificado();
})();

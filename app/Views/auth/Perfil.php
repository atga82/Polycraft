<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mi perfil | Polycraft</title><link rel="stylesheet" href="<?= e($base) ?>/assets/css/stylemenu.css">
<style>.pc-perfil{max-width:720px;margin:30px auto;padding:25px;background:white;border-radius:12px}.pc-perfil label{display:block;margin:15px 0}.pc-perfil input,.pc-perfil select{display:block;width:100%;max-width:560px;padding:10px;border:1px solid #aaa;border-radius:6px}.pc-perfil button{padding:12px 22px}.pc-error{color:#a32121}</style>
</head><body>
<nav class="navbar"><div class="navbar-left"><a href="<?= e($base) ?>/" class="logo">Polycraft</a><a href="<?= e($base) ?>/" class="nav-link">Catálogo</a></div><div class="navbar-right"><?php include BASE_PATH . '/app/Views/partials/sesion.php'; ?></div></nav>
<main class="pc-perfil"><h1>Mi perfil</h1><p id="perfil-estado" role="status">Cargando datos...</p>
<form id="perfil-form">
<label>Nombre completo <input name="nombre_completo" required minlength="2" maxlength="120"></label>
<label>Correo electrónico <input name="email" type="email" required maxlength="150"></label>
<label>Género <select name="genero"><option value="masculino">Masculino</option><option value="femenino">Femenino</option><option value="otro">Otro</option></select></label>
<label>Fecha de nacimiento <input name="fecha_nacimiento" type="date" required></label>
<h2>Cambiar contraseña (opcional)</h2>
<label>Contraseña actual <input name="password_actual" type="password" autocomplete="current-password"></label>
<label>Contraseña nueva <input name="password_nueva" type="password" autocomplete="new-password" minlength="8"></label>
<p>La nueva contraseña requiere mayúscula, número y carácter especial.</p>
<button class="btn btn-filled" type="submit">Guardar cambios</button></form>
<hr><h2>Avatar</h2>
<form id="avatar-form"><label>Imagen (JPG, PNG o WebP, máximo 2 MB)
<input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required></label>
<button class="btn btn-outline">Subir avatar</button></form></main>
<script>
(() => {
const base=<?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const f=document.getElementById('perfil-form'),estado=document.getElementById('perfil-estado');
async function req(path,options={}){
    const r=await fetch(base+path,{credentials:'same-origin',headers:{Accept:'application/json'},...options});
    const j=await r.json();
    if(!r.ok||!j.ok)throw new Error(j.error?.mensaje||'Error HTTP '+r.status);
    return j.data;
}
async function cargar(){
    try{
        const d=await req('/api/perfil');
        for(const k of ['nombre_completo','email','genero','fecha_nacimiento'])f.elements[k].value=d[k]||'';
        estado.textContent='Sesión activa como '+d.rol+'.';
    }catch(e){estado.textContent=e.message+' Inicia sesión para editar tu perfil.';}
}
f.addEventListener('submit',async e=>{
    e.preventDefault();estado.textContent='Guardando cambios...';
    const d={};
    for(const k of ['nombre_completo','email','genero','fecha_nacimiento'])d[k]=f.elements[k].value.trim();
    if(f.elements.password_nueva.value){
        d.password_nueva=f.elements.password_nueva.value;
        d.password_actual=f.elements.password_actual.value;
    }
    try{
        await req('/api/perfil',{method:'PATCH',headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify(d)});
        f.elements.password_actual.value='';f.elements.password_nueva.value='';
        estado.textContent='Perfil actualizado correctamente.';
    }catch(error){estado.textContent=error.message;}
});
document.getElementById('avatar-form').addEventListener('submit',async e=>{
    e.preventDefault();estado.textContent='Subiendo avatar...';
    const formData=new FormData(e.target);
    try{
        await req('/api/perfil/avatar',{method:'POST',body:formData});
        estado.textContent='Avatar actualizado. Se mostrará en el menú cuando recargues la página.';
    }catch(error){estado.textContent=error.message;}
});
cargar();
})();
</script>
</body></html>
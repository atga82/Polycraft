<?php /* Zona derecha del navbar. Necesita $base, $usuario (o null) y $csrf. */ ?>
<?php if (!empty($usuario)): ?>
    <span class="nav-usuario">
        <img src="<?= $base ?>/uploads/avatars/<?= e($usuario['avatar']) ?>" alt="" class="nav-avatar">
        <?= e($usuario['nombre_completo']) ?> <small>(<?= e($usuario['rol']) ?>)</small>
    </span>
    <a href="<?= e($base) ?>/perfil" class="btn btn-outline">Mi perfil</a>
    <form action="<?= $base ?>/logout" method="POST" class="form-logout">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="btn btn-outline">Cerrar sesión</button>
    </form>
<?php else: ?>
    <a href="<?= $base ?>/login" class="btn btn-outline">Iniciar sesión</a>
    <a href="<?= $base ?>/registro" class="btn btn-filled">Regístrate</a>
<?php endif; ?>

<?php
/**
 * Vista: Editar Usuario
 * RF_12 — Solo Coordinador (Rol 1)
 */
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index">Usuarios</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Editar Usuario</span>
</div>

<div class="d-flex align-items-center mb-4 gap-3 fade-in-up">
    <div class="user-avatar-sm" style="width:44px; height:44px; font-size:1rem;">
        <?php
        $initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $usuario['nombre_completo']), 0, 2)));
        echo htmlspecialchars($initials);
        ?>
    </div>
    <div>
        <h1 class="h4 mb-0">Editar: <?= htmlspecialchars($usuario['nombre_completo']) ?></h1>
        <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
            Clave: <span class="folio-tag"><?= htmlspecialchars($usuario['clave_acceso']) ?></span>
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong>Corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7 fade-in-up delay-1">
        <div class="hd-card">
            <div class="hd-card-header">
                <h2 class="hd-card-title"><i class="bi bi-pencil-square text-accent"></i> Modificar Datos</h2>
            </div>
            <div class="hd-card-body">
                <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Usuario&action=update" novalidate>
                    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Clave de Acceso</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['clave_acceso']) ?>" disabled>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">La clave no puede modificarse</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="id_rol" class="form-label">
                                Rol <span style="color:var(--danger);">*</span>
                            </label>
                            <select class="form-select" id="id_rol" name="id_rol" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id'] ?>"
                                        <?= $usuario['id_rol'] == $rol['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rol['nombre_rol']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="nombre_completo" class="form-label">
                                Nombre Completo <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                       value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="correo" class="form-label">
                                Correo Institucional <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="correo" name="correo"
                                       value="<?= htmlspecialchars($usuario['correo_institucional']) ?>" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activo"   <?= $usuario['estado'] === 'activo'   ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $usuario['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="col-12"><hr></div>

                        <!-- Sección de cambio de contraseña (opcional) -->
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-lock text-accent"></i>
                                <span style="font-size:0.85rem; font-weight:600; color:var(--text-secondary);">
                                    Cambiar Contraseña <span style="font-weight:400; color:var(--text-muted);">(opcional)</span>
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="nueva_password" class="form-label">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="nueva_password" name="nueva_password"
                                       placeholder="Dejar vacío para no cambiar" minlength="8"
                                       autocomplete="new-password">
                            </div>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">Mínimo 8 caracteres si deseas cambiarla</div>
                        </div>

                    </div><!-- /row -->

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>

<?php
/**
 * Vista: Catálogo de Usuarios
 * RF_13 — Solo Coordinador (Rol 1)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Detectar si hay filtros activos (para diferenciar el empty-state)
$filtrosActivos = !empty($filtros['nombre'])
               || !empty($filtros['id_rol'])
               || !empty($filtros['estado']);

// Mapas de color por rol para los badges
function rolBadgeClass(string $rol): string {
    return match (strtolower(trim($rol))) {
        'coordinador'                      => 'badge-alta',
        'soporte técnico', 'soporte tecnico' => 'badge-proceso',
        'mesa de ayuda'                    => 'badge-abierto',
        default                            => 'badge-pendiente',
    };
}
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
        <i class="bi bi-house-fill"></i>
    </a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Catálogo de Usuarios</span>
</div>

<!-- Flash messages -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= $flashSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Encabezado -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1">
            <i class="bi bi-people me-2 text-accent"></i>Catálogo de Usuarios
        </h1>
        <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
            <?php if ($filtrosActivos): ?>
                <span class="text-accent fw-semibold"><?= count($usuarios) ?></span>
                resultado(s) encontrado(s)
                &mdash;
                <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index"
                   style="color:var(--text-muted); text-decoration:none; font-size:0.8rem;">
                    <i class="bi bi-x-circle"></i> Limpiar filtros
                </a>
            <?php else: ?>
                <span class="text-accent fw-semibold"><?= count($usuarios) ?></span>
                usuario(s) registrado(s) en el sistema
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=create"
       class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
    </a>
</div>

<!-- Filtros -->
<div class="hd-card mb-4 fade-in-up delay-1">
    <div class="hd-card-header">
        <h2 class="hd-card-title">
            <i class="bi bi-funnel text-accent"></i> Filtros de Búsqueda
        </h2>
        <?php if ($filtrosActivos): ?>
            <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle"></i> Limpiar
            </a>
        <?php endif; ?>
    </div>
    <div class="hd-card-body">
        <form method="GET" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="controller" value="Usuario">
            <input type="hidden" name="action"     value="index">
            <div class="row g-3 align-items-end">

                <!-- Nombre -->
                <div class="col-12 col-md-4">
                    <label class="form-label" for="nombre">Buscar por Nombre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               placeholder="Nombre del usuario..."
                               value="<?= htmlspecialchars($filtros['nombre'] ?? '') ?>">
                    </div>
                </div>

                <!-- Rol -->
                <div class="col-12 col-md-3">
                    <label class="form-label" for="id_rol">Filtrar por Rol</label>
                    <select class="form-select" id="id_rol" name="id_rol">
                        <option value="">Todos los roles</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>"
                                <?= ($filtros['id_rol'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Estado -->
                <div class="col-12 col-md-3">
                    <label class="form-label" for="estado">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo"   <?= ($filtros['estado'] ?? '') === 'activo'   ? 'selected' : '' ?>>
                            Activo
                        </option>
                        <option value="inactivo" <?= ($filtros['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>
                            Inactivo
                        </option>
                    </select>
                </div>

                <!-- Botón -->
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Tabla de Usuarios -->
<div class="hd-card fade-in-up delay-2">
    <div class="hd-card-body p-0">
        <?php if (empty($usuarios)): ?>
            <div class="empty-state py-5">
                <i class="bi bi-people d-block mb-3" style="font-size:2.5rem; color:var(--text-muted);"></i>
                <?php if ($filtrosActivos): ?>
                    <p class="mb-2">No se encontraron usuarios con los filtros aplicados.</p>
                    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Limpiar filtros
                    </a>
                <?php else: ?>
                    <p class="mb-2">No hay usuarios registrados en el sistema aún.</p>
                    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=create"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus"></i> Crear primer usuario
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th class="d-none d-sm-table-cell">Clave</th>
                            <th class="d-none d-md-table-cell">Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <?php
                            $words    = array_slice(explode(' ', $u['nombre_completo']), 0, 2);
                            $initials = implode('', array_map(fn($w) => strtoupper($w[0]), $words));
                            $isActivo = strtolower($u['estado']) === 'activo';
                            ?>
                            <tr>
                                <!-- Nombre + Avatar -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-sm flex-shrink-0 <?= $isActivo ? '' : 'opacity-50' ?>">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                        <span class="text-truncate"
                                              style="font-weight:500; max-width:160px; <?= $isActivo ? '' : 'color:var(--text-muted);' ?>">
                                            <?= htmlspecialchars($u['nombre_completo']) ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Clave de empleado -->
                                <td class="d-none d-sm-table-cell">
                                    <span class="folio-tag">
                                        <?= htmlspecialchars($u['clave_acceso']) ?>
                                    </span>
                                </td>

                                <!-- Correo -->
                                <td class="d-none d-md-table-cell"
                                    style="font-size:0.82rem; color:var(--text-muted); max-width:200px;">
                                    <span class="text-truncate d-block">
                                        <?= htmlspecialchars($u['correo_institucional']) ?>
                                    </span>
                                </td>

                                <!-- Rol -->
                                <td>
                                    <span class="hd-badge <?= rolBadgeClass($u['nombre_rol']) ?>">
                                        <?= htmlspecialchars($u['nombre_rol']) ?>
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td>
                                    <span class="hd-badge <?= $isActivo ? 'badge-activo' : 'badge-inactivo' ?>">
                                        <i class="bi bi-circle-fill me-1" style="font-size:0.45rem;"></i>
                                        <?= $isActivo ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>

                                <!-- Accion -->
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=edit&id=<?= $u['id'] ?>"
                                       class="btn btn-outline-primary btn-sm"
                                       title="Editar perfil de <?= htmlspecialchars($u['nombre_completo']) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <span class="d-none d-md-inline ms-1">Editar</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>

<?php
/**
 * Vista: Catálogo de Usuarios
 * RF_13 — Solo Coordinador (Rol 1)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Catálogo de Usuarios</span>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-check-circle-fill"></i><span><?= $flashSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-circle-fill"></i><span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-people me-2 text-accent"></i>Catálogo de Usuarios</h1>
        <p class="mb-0" style="font-size:0.82rem;">
            <span class="text-accent fw-semibold"><?= count($usuarios) ?></span> usuario(s) registrados
        </p>
    </div>
    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=create" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
    </a>
</div>

<!-- Filtros -->
<div class="hd-card mb-4 fade-in-up delay-1">
    <div class="hd-card-header">
        <h2 class="hd-card-title"><i class="bi bi-funnel text-accent"></i> Filtros</h2>
        <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle"></i> Limpiar
        </a>
    </div>
    <div class="hd-card-body">
        <form method="GET" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="controller" value="Usuario">
            <input type="hidden" name="action" value="index">
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="nombre">Buscar por Nombre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               placeholder="Nombre del usuario..."
                               value="<?= htmlspecialchars($filtros['nombre'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="id_rol">Filtrar por Rol</label>
                    <select class="form-select" id="id_rol" name="id_rol">
                        <option value="">Todos los roles</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>" <?= ($filtros['id_rol'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto d-flex align-items-end">
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
            <div class="empty-state">
                <i class="bi bi-people d-block"></i>
                <p>No se encontraron usuarios con los filtros aplicados.</p>
            </div>
        <?php else: ?>
            <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Clave</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <?php
                            $initials = implode('', array_map(
                                fn($w) => strtoupper($w[0]),
                                array_slice(explode(' ', $u['nombre_completo']), 0, 2)
                            ));
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-sm"><?= htmlspecialchars($initials) ?></div>
                                        <span style="font-weight:500;"><?= htmlspecialchars($u['nombre_completo']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="folio-tag"><?= htmlspecialchars($u['clave_acceso']) ?></span>
                                </td>
                                <td style="font-size:0.82rem; color:var(--text-muted);">
                                    <?= htmlspecialchars($u['correo_institucional']) ?>
                                </td>
                                <td>
                                    <span class="hd-badge badge-abierto"><?= htmlspecialchars($u['nombre_rol']) ?></span>
                                </td>
                                <td>
                                    <span class="hd-badge <?= strtolower($u['estado']) === 'activo' ? 'badge-activo' : 'badge-inactivo' ?>">
                                        <i class="bi bi-circle-fill" style="font-size:0.5rem;"></i>
                                        <?= ucfirst($u['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=edit&id=<?= $u['id'] ?>"
                                       class="btn btn-outline-primary btn-sm" title="Editar usuario">
                                        <i class="bi bi-pencil-square"></i>
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

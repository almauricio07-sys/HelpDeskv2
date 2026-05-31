/**
 * main.js — Scripts globales del sistema
 * Sistema de Mesa de Ayuda - Los Bélicos
 */

(function () {
    'use strict';

    // ── Auto-dismiss de alertas después de 5 segundos ──────────────────────
    document.querySelectorAll('.alert.alert-success, .alert.alert-danger').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 5000);
    });

    // ── Activar tooltips de Bootstrap ──────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // ── Confirmar acciones destructivas ────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ── Resaltar fila de tabla al hacer click ──────────────────────────────
    document.querySelectorAll('.table tbody tr').forEach(function (row) {
        row.style.cursor = 'pointer';
    });

    // NOTA: El autocompletado AJAX del solicitante (Upsert) vive en el script
    // inline de app/views/tickets/create.php, donde sí se interpreta BASE_URL
    // de PHP. No debe duplicarse aquí (este archivo .js no pasa por PHP).

    // ── Navegación activa en la navbar ─────────────────────────────────────
    // (Ya se maneja desde PHP con la función isActive() en header.php)

    console.info('%cMesa de Ayuda — Los Bélicos', 'color:#0d6efd; font-weight:700; font-size:14px;');
})();

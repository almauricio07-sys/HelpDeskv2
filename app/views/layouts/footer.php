    </div><!-- /container-fluid -->
</main>
<!-- ═══ FOOTER ══════════════════════════════════════════════════════════════ -->
<footer class="hd-footer">
    <div class="d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-headset text-accent"></i>
        <span>Sistema de Mesa de Ayuda &mdash; <strong style="color:var(--text-secondary)">Los Bélicos</strong></span>
        <span class="text-muted">&bull;</span>
        <span><?= date('Y') ?></span>
    </div>
</footer>
<!-- ═══ / FOOTER ═════════════════════════════════════════════════════════════ -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js (para dashboards) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Scripts propios -->
<script src="<?= BASE_URL ?>/public/js/main.js"></script>

<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>

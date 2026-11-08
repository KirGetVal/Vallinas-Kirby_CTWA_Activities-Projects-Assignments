<script src="<?= e(base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script>
window.bootstrap || document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"><\/script>');
</script>
<?php if (!empty($GLOBALS['cms_needs_chartjs'])): ?>
<script src="<?= e(base_url('assets/vendor/chartjs/chart.umd.min.js')) ?>"></script>
<script>
window.Chart || document.write('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"><\/script>');
</script>
<?php endif; ?>
<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
<?= $GLOBALS['cms_page_scripts'] ?? '' ?>
</body>
</html>

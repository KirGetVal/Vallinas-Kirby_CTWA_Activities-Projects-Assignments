// Renders dashboard/report charts from server-supplied JSON (window.CMS_CHARTS). No hardcoded data.
(function () {
  if (!window.CMS_CHARTS) { return; }
  if (!window.Chart) {   // offline and no local copy: say so instead of leaving blank boxes
    document.querySelectorAll('.chart-box').forEach(function (b) {
      b.innerHTML = '<p class="text-muted small text-center mt-5">Charts need Chart.js. Download chart.umd.min.js into assets/vendor/chartjs/ for offline use.</p>';
    });
    return;
  }
  var D = window.CMS_CHARTS;
  var palette = ['#1E3A8A', '#0D9488', '#F59E0B', '#EF4444', '#6366F1', '#10B981', '#64748B', '#EC4899', '#0EA5E9'];
  function make(id, type, map, opts) {
    var el = document.getElementById(id);
    if (!el || !map) { return; }
    var labels = Object.keys(map), values = labels.map(function (k) { return map[k]; });
    if (!labels.length) { return; }
    var multi = type === 'doughnut';
    new Chart(el, {
      type: type,
      data: { labels: labels, datasets: [{ data: values, label: 'Complaints',
        backgroundColor: multi ? palette : '#0D9488', borderColor: multi ? '#fff' : '#0D9488', borderWidth: multi ? 2 : 1 }] },
      options: Object.assign({ responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: multi, position: 'bottom', labels: { boxWidth: 12 } } },
        scales: multi ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } } }, opts || {})
    });
  }
  make('chartStatus', 'doughnut', D.status);
  make('chartMonthly', 'bar', D.monthly);
  make('chartCategory', 'bar', D.category, { indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } });
  make('chartStatusR', 'doughnut', D.status);
  make('chartMonthlyR', 'bar', D.monthly);
  make('chartCategoryR', 'doughnut', D.category);
})();

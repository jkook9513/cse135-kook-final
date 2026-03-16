let pageActivityChartInstance = null;
let eventTypeChartInstance = null;
let siteOriginChartInstance = null;

let cachedPageRows = [];
let cachedEventTypeRows = [];
let cachedSiteOriginRows = [];
let cachedRecentEventRows = [];

async function fetchJson(url) {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Request failed: ${url}`);
  }
  return response.json();
}

function showDashboardError(message) {
  const errorBox = document.getElementById('dashboard-error');
  if (!errorBox) return;
  errorBox.style.display = 'block';
  errorBox.textContent = message;
}

function updateSummaryCards() {
  const totalEvents = cachedPageRows.reduce((sum, row) => sum + Number(row.views || 0), 0);

  const uniqueSessions = new Set(
    cachedRecentEventRows
      .map(row => row.session_id)
      .filter(value => value && value.trim() !== '')
  ).size;

  const topEventType = cachedEventTypeRows.length > 0
    ? cachedEventTypeRows[0].event_type
    : '—';

  const topOrigin = cachedSiteOriginRows.length > 0
    ? cachedSiteOriginRows[0].site_origin
    : '—';

  document.getElementById('summary-total-events').textContent = totalEvents || '0';
  document.getElementById('summary-unique-sessions').textContent = uniqueSessions || '0';
  document.getElementById('summary-top-event-type').textContent = topEventType || '—';
  document.getElementById('summary-top-origin').textContent = topOrigin || '—';
}

async function loadRecentEventsTable() {
  const rows = await fetchJson('api/table.php');
  cachedRecentEventRows = rows;

  const tbody = document.getElementById('table-body');
  tbody.innerHTML = '';

  for (const row of rows) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.id ?? ''}</td>
      <td>${row.received_at ?? ''}</td>
      <td>${row.event_type ?? ''}</td>
      <td>${row.page_url ?? ''}</td>
      <td>${row.session_id ?? ''}</td>
    `;
    tbody.appendChild(tr);
  }
}

async function loadPageActivity() {
  const rows = await fetchJson('api/chart.php');
  cachedPageRows = rows;

  const pageTable = document.getElementById('page-table-body');
  pageTable.innerHTML = '';

  for (const row of rows) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.page_url ?? ''}</td>
      <td>${row.views ?? 0}</td>
    `;
    pageTable.appendChild(tr);
  }

  const ctx = document.getElementById('viewsChart').getContext('2d');

  if (pageActivityChartInstance) {
    pageActivityChartInstance.destroy();
  }

  pageActivityChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: rows.map(row => row.page_url),
      datasets: [{
        label: 'Event Count',
        data: rows.map(row => Number(row.views)),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

async function loadEventTypes() {
  const rows = await fetchJson('api/event_types.php');
  cachedEventTypeRows = rows;

  const ctx = document.getElementById('eventTypeChart').getContext('2d');

  if (eventTypeChartInstance) {
    eventTypeChartInstance.destroy();
  }

  eventTypeChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: rows.map(row => row.event_type),
      datasets: [{
        label: 'Event Type Count',
        data: rows.map(row => Number(row.total))
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });
}

async function loadSiteOrigins() {
  const rows = await fetchJson('api/site_origins.php');
  cachedSiteOriginRows = rows;

  const tbody = document.getElementById('origin-table-body');
  tbody.innerHTML = '';

  for (const row of rows) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.site_origin ?? ''}</td>
      <td>${row.total ?? 0}</td>
    `;
    tbody.appendChild(tr);
  }

  const ctx = document.getElementById('siteOriginChart').getContext('2d');

  if (siteOriginChartInstance) {
    siteOriginChartInstance.destroy();
  }

  siteOriginChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: rows.map(row => row.site_origin),
      datasets: [{
        label: 'Origin Count',
        data: rows.map(row => Number(row.total))
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });
}

function submitPdfExport() {
  const pageCanvas = document.getElementById('viewsChart');
  const eventCanvas = document.getElementById('eventTypeChart');
  const originCanvas = document.getElementById('siteOriginChart');

  document.getElementById('chart_page_activity').value = pageCanvas.toDataURL('image/png');
  document.getElementById('chart_event_types').value = eventCanvas.toDataURL('image/png');
  document.getElementById('chart_site_origins').value = originCanvas.toDataURL('image/png');

  document.getElementById('export_comment_page_activity').value =
    document.getElementById('comment_page_activity').value;

  document.getElementById('export_comment_event_types').value =
    document.getElementById('comment_event_types').value;

  document.getElementById('export_comment_site_origin').value =
    document.getElementById('comment_site_origin').value;

  document.getElementById('pdfExportForm').submit();
}

async function initDashboard() {
  await loadPageActivity();
  await loadRecentEventsTable();
  await loadEventTypes();
  await loadSiteOrigins();
  updateSummaryCards();

  const exportBtn = document.getElementById('exportPdfBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', submitPdfExport);
  }
}

initDashboard().catch(err => {
  console.error(err);
  showDashboardError('Dashboard failed to load completely. Check the API endpoints and browser console.');
});

<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_role(['super_admin', 'analyst']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Analytics Reports</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --bg: #f6f8fb;
      --card: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --line: #d1d5db;
      --accent: #2563eb;
      --shadow: 0 8px 24px rgba(0,0,0,0.08);
      --radius: 14px;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: #0f172a;
      color: #fff;
      padding: 14px 24px;
      box-shadow: var(--shadow);
    }

    .topbar-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .brand {
      font-size: 1.15rem;
      font-weight: bold;
    }

    .topbar-links {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .topbar-links a,
    .topbar-links button {
      text-decoration: none;
      border: none;
      background: #1e293b;
      color: #fff;
      padding: 10px 14px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 0.95rem;
    }

    .topbar-links a:hover,
    .topbar-links button:hover {
      background: #334155;
    }

    .page {
      max-width: 1100px;
      margin: 24px auto;
      padding: 0 20px 40px;
    }

    .hero {
      background: linear-gradient(135deg, #dbeafe, #eff6ff);
      border: 1px solid #bfdbfe;
      border-radius: var(--radius);
      padding: 24px;
      box-shadow: var(--shadow);
      margin-bottom: 22px;
    }

    .hero h1 {
      margin: 0 0 10px;
      font-size: 2rem;
    }

    .hero p {
      margin: 6px 0;
      color: var(--muted);
      line-height: 1.5;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin: 22px 0;
    }

    .summary-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 18px;
      box-shadow: var(--shadow);
    }

    .summary-card .label {
      font-size: 0.9rem;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .summary-card .value {
      font-size: 1.65rem;
      font-weight: bold;
      line-height: 1.2;
      word-break: break-word;
    }

    .section-nav {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 20px 0 24px;
    }

    .section-nav a {
      text-decoration: none;
      background: var(--card);
      color: var(--text);
      border: 1px solid var(--line);
      padding: 10px 14px;
      border-radius: 999px;
      box-shadow: var(--shadow);
      font-size: 0.95rem;
    }

    .section-nav a:hover {
      border-color: var(--accent);
      color: var(--accent);
    }

    .report-section {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 22px;
      box-shadow: var(--shadow);
      margin-bottom: 24px;
      scroll-margin-top: 90px;
    }

    .report-section h2 {
      margin-top: 0;
      margin-bottom: 8px;
    }

    .section-description {
      color: var(--muted);
      margin-bottom: 18px;
      line-height: 1.55;
    }

    .table-card,
    .chart-card,
    .comment-card {
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 16px;
      background: #fff;
      margin-top: 18px;
    }

    .table-card h3,
    .chart-card h3,
    .comment-card h3 {
      margin-top: 0;
      margin-bottom: 12px;
    }

    .table-scroll {
      width: 100%;
      overflow-x: auto;
      overflow-y: auto;
      max-height: 340px;
      border-radius: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
      min-width: 720px;
    }

    th, td {
      border: 1px solid var(--line);
      padding: 8px 10px;
      text-align: left;
      vertical-align: top;
    }

    th {
      background: #f3f4f6;
    }

    .chart-wrap {
      width: 100%;
      max-width: 900px;
      height: 340px;
      margin: 0 auto;
      position: relative;
    }

    textarea {
      width: 100%;
      min-height: 120px;
      resize: vertical;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 12px;
      font: inherit;
      line-height: 1.5;
      color: var(--text);
      background: #fff;
    }

    .error-box {
      margin-top: 16px;
      padding: 14px;
      border-radius: 10px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      display: none;
    }

    .footer-note {
      color: var(--muted);
      font-size: 0.92rem;
      margin-top: 10px;
    }

    @media (max-width: 900px) {
      .page {
        padding: 0 14px 30px;
      }

      .report-section,
      .hero,
      .summary-card {
        padding: 16px;
      }

      .chart-wrap {
        height: 280px;
      }

      table {
        min-width: 620px;
      }
    }

    @media (max-width: 600px) {
      .topbar {
        padding: 12px 14px;
      }

      .chart-wrap {
        height: 240px;
      }

      .summary-card .value {
        font-size: 1.4rem;
      }

      table {
        min-width: 560px;
      }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <div>
        <div class="brand">Analytics Reporting Platform</div>
        <div style="font-size:0.9rem; color:#cbd5e1;">
          Logged in as <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
          (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)
        </div>
      </div>

      <div class="topbar-links">
        <a href="saved_reports.php">Saved Reports</a>
        <button type="button" id="exportPdfBtn">Export PDF</button>
        <a href="logout.php">Log Out</a>
      </div>
    </div>
  </div>

  <div class="page">
    <section class="hero">
      <h1>Analytics Reports Dashboard</h1>
      <p>
        This dashboard presents tracked collector activity through three report categories:
        page activity, event types, and site origin analysis.
      </p>
      <p class="footer-note">
        The export button generates a styled PDF snapshot of the current dashboard, including chart images, tables, and analyst comments.
      </p>
    </section>

    <div class="summary-grid">
      <div class="summary-card">
        <div class="label">Total Events</div>
        <div class="value" id="summary-total-events">—</div>
      </div>
      <div class="summary-card">
        <div class="label">Unique Sessions</div>
        <div class="value" id="summary-unique-sessions">—</div>
      </div>
      <div class="summary-card">
        <div class="label">Top Event Type</div>
        <div class="value" id="summary-top-event-type">—</div>
      </div>
      <div class="summary-card">
        <div class="label">Most Active Origin</div>
        <div class="value" id="summary-top-origin">—</div>
      </div>
    </div>

    <div class="section-nav">
      <a href="#page-activity">Page Activity</a>
      <a href="#event-types">Event Types</a>
      <a href="#site-origin">Site Origin</a>
    </div>

    <div class="error-box" id="dashboard-error"></div>

    <section class="report-section" id="page-activity">
      <h2>1. Page Activity Report</h2>
      <p class="section-description">
        This report highlights the pages receiving the most tracked events. It helps identify where user activity is concentrated and which destinations drive the most interactions.
      </p>

      <div class="table-card">
        <h3>Top Pages Table</h3>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Page URL</th>
                <th>Event Count</th>
              </tr>
            </thead>
            <tbody id="page-table-body"></tbody>
          </table>
        </div>
      </div>

      <div class="chart-card">
        <h3>Top Pages by Event Count</h3>
        <div class="chart-wrap">
          <canvas id="viewsChart"></canvas>
        </div>
      </div>

      <div class="comment-card">
        <h3>Analyst Comments</h3>
        <textarea id="comment_page_activity">The page activity data suggests that a small number of pages account for most tracked interactions. This helps identify which destinations attract the strongest attention and where additional optimization or monitoring may be most valuable.</textarea>
      </div>
    </section>

    <section class="report-section" id="event-types">
      <h2>2. Event Type Analysis</h2>
      <p class="section-description">
        This report shows the most common interaction types observed by the collector. It is useful for understanding what users are actually doing, not just where they go.
      </p>

      <div class="table-card">
        <h3>Recent Events Snapshot</h3>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Received At</th>
                <th>Event Type</th>
                <th>Page URL</th>
                <th>Session ID</th>
              </tr>
            </thead>
            <tbody id="table-body"></tbody>
          </table>
        </div>
      </div>

      <div class="chart-card">
        <h3>Event Type Distribution</h3>
        <div class="chart-wrap">
          <canvas id="eventTypeChart"></canvas>
        </div>
      </div>

      <div class="comment-card">
        <h3>Analyst Comments</h3>
        <textarea id="comment_event_types">The event type distribution shows which interaction patterns dominate the observed activity. This helps distinguish between passive usage, such as page views, and more deliberate engagement behaviors captured by the tracker.</textarea>
      </div>
    </section>

    <section class="report-section" id="site-origin">
      <h2>3. Site Origin Analysis</h2>
      <p class="section-description">
        This report groups tracked activity by site origin so behavior can be compared across the monitored environments.
      </p>

      <div class="table-card">
        <h3>Site Origin Table</h3>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Site Origin</th>
                <th>Event Count</th>
              </tr>
            </thead>
            <tbody id="origin-table-body"></tbody>
          </table>
        </div>
      </div>

      <div class="chart-card">
        <h3>Origin Event Counts</h3>
        <div class="chart-wrap">
          <canvas id="siteOriginChart"></canvas>
        </div>
      </div>

      <div class="comment-card">
        <h3>Analyst Comments</h3>
        <textarea id="comment_site_origin">Comparing site origins helps show where tracked activity is concentrated across environments. Large differences between origins may indicate uneven usage patterns, differences in testing traffic, or content concentration in specific sites.</textarea>
      </div>
    </section>
  </div>

  <form id="pdfExportForm" method="post" action="api/export.php" style="display:none;">
    <input type="hidden" name="category" value="all">
    <input type="hidden" name="chart_page_activity" id="chart_page_activity">
    <input type="hidden" name="chart_event_types" id="chart_event_types">
    <input type="hidden" name="chart_site_origins" id="chart_site_origins">

    <input type="hidden" name="comment_page_activity" id="export_comment_page_activity">
    <input type="hidden" name="comment_event_types" id="export_comment_event_types">
    <input type="hidden" name="comment_site_origin" id="export_comment_site_origin">
  </form>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="js/dashboard.js"></script>
</body>
</html>

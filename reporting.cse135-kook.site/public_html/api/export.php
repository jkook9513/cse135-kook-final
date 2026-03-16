<?php
declare(strict_types=1);

require_once __DIR__ . '/_db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

session_start();

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['super_admin', 'analyst'], true)
) {
    http_response_code(403);
    exit('Forbidden');
}

$pdo = db();

$category = $_POST['category'] ?? ($_GET['category'] ?? 'all');

$chartPageActivity = $_POST['chart_page_activity'] ?? '';
$chartEventTypes = $_POST['chart_event_types'] ?? '';
$chartSiteOrigins = $_POST['chart_site_origins'] ?? '';

$commentPageActivity = trim($_POST['comment_page_activity'] ?? '');
$commentEventTypes = trim($_POST['comment_event_types'] ?? '');
$commentSiteOrigin = trim($_POST['comment_site_origin'] ?? '');

$timestamp = date('Ymd_His');
$filename = "report_{$category}_{$timestamp}.pdf";

$exportsDir = dirname(__DIR__) . '/exports';
$relativePath = 'exports/' . $filename;
$absolutePath = $exportsDir . '/' . $filename;

if (!is_dir($exportsDir)) {
    http_response_code(500);
    exit('Exports directory does not exist: ' . $exportsDir);
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderChartImage(string $dataUrl, string $title): string {
    if ($dataUrl === '') {
        return '
          <div class="chart-placeholder">
            No chart image was available for ' . h($title) . '.
          </div>
        ';
    }

    return '
      <div class="chart-block">
        <img src="' . h($dataUrl) . '" alt="' . h($title) . '">
      </div>
    ';
}

/*
|--------------------------------------------------------------------------
| Query report data
|--------------------------------------------------------------------------
*/

$topPagesStmt = $pdo->query("
    SELECT page_url, COUNT(*) AS views
    FROM collector_events
    WHERE page_url IS NOT NULL AND page_url <> ''
    GROUP BY page_url
    ORDER BY views DESC
    LIMIT 10
");
$topPages = $topPagesStmt->fetchAll();

$eventTypesStmt = $pdo->query("
    SELECT event_type, COUNT(*) AS total
    FROM collector_events
    WHERE event_type IS NOT NULL AND event_type <> ''
    GROUP BY event_type
    ORDER BY total DESC
");
$eventTypes = $eventTypesStmt->fetchAll();

$siteOriginsStmt = $pdo->query("
    SELECT site_origin, COUNT(*) AS total
    FROM collector_events
    WHERE site_origin IS NOT NULL AND site_origin <> ''
    GROUP BY site_origin
    ORDER BY total DESC
");
$siteOrigins = $siteOriginsStmt->fetchAll();

$recentEventsStmt = $pdo->query("
    SELECT id, received_at, event_type, page_url, session_id
    FROM collector_events
    ORDER BY received_at DESC
    LIMIT 15
");
$recentEvents = $recentEventsStmt->fetchAll();

$totalEventsStmt = $pdo->query("
    SELECT COUNT(*) AS total_events
    FROM collector_events
");
$totalEventsRow = $totalEventsStmt->fetch();
$totalEvents = (string)($totalEventsRow['total_events'] ?? '0');

$uniqueSessionsStmt = $pdo->query("
    SELECT COUNT(DISTINCT session_id) AS total_sessions
    FROM collector_events
    WHERE session_id IS NOT NULL AND session_id <> ''
");
$uniqueSessionsRow = $uniqueSessionsStmt->fetch();
$uniqueSessions = (string)($uniqueSessionsRow['total_sessions'] ?? '0');

$topEventTypeValue = $eventTypes[0]['event_type'] ?? 'N/A';
$topOriginValue = $siteOrigins[0]['site_origin'] ?? 'N/A';

/*
|--------------------------------------------------------------------------
| Build styled HTML for PDF
|--------------------------------------------------------------------------
*/

$html = '
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Analytics Report Export</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #1f2937;
      line-height: 1.45;
      margin: 0;
      padding: 0;
    }

    .page-wrap {
      padding: 24px 28px;
    }

    .header {
      border-bottom: 3px solid #2563eb;
      padding-bottom: 12px;
      margin-bottom: 18px;
    }

    .header h1 {
      font-size: 24px;
      margin: 0 0 8px;
    }

    .meta {
      margin: 4px 0;
      color: #4b5563;
    }

    .summary-grid {
      width: 100%;
      margin: 16px 0 22px;
      border-collapse: separate;
      border-spacing: 8px;
    }

    .summary-grid td {
      width: 25%;
      vertical-align: top;
    }

    .summary-card {
      border: 1px solid #cbd5e1;
      background: #f8fafc;
      padding: 10px;
      min-height: 72px;
    }

    .summary-label {
      font-size: 11px;
      color: #6b7280;
      margin-bottom: 6px;
    }

    .summary-value {
      font-size: 18px;
      font-weight: bold;
      color: #111827;
    }

    .report-section {
      margin-top: 24px;
      padding-top: 8px;
    }

    .report-section h2 {
      font-size: 17px;
      margin: 0 0 8px;
      border-bottom: 1px solid #d1d5db;
      padding-bottom: 4px;
    }

    .section-text {
      margin: 8px 0 12px;
      color: #374151;
    }

    .chart-block {
      margin: 12px 0 18px;
      text-align: center;
    }

    .chart-block img {
      width: 100%;
      max-width: 650px;
      border: 1px solid #d1d5db;
    }

    .chart-placeholder {
      margin: 12px 0 18px;
      padding: 12px;
      border: 1px dashed #9ca3af;
      color: #6b7280;
      background: #f9fafb;
    }

    .comment-box {
      margin: 12px 0 16px;
      padding: 12px;
      border-left: 4px solid #2563eb;
      background: #eff6ff;
    }

    .comment-title {
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      color: #1d4ed8;
      margin-bottom: 6px;
    }

    table.data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      margin-bottom: 18px;
      table-layout: fixed;
    }

    table.data-table th,
    table.data-table td {
      border: 1px solid #9ca3af;
      padding: 6px 8px;
      text-align: left;
      vertical-align: top;
      word-wrap: break-word;
    }

    table.data-table th {
      background: #e5e7eb;
      font-weight: bold;
    }

    .small-note {
      font-size: 11px;
      color: #6b7280;
      margin-top: 18px;
    }
  </style>
</head>
<body>
  <div class="page-wrap">
    <div class="header">
      <h1>Analytics Report Export</h1>
      <p class="meta"><strong>Category:</strong> ' . h($category) . '</p>
      <p class="meta"><strong>Generated at:</strong> ' . h(date('Y-m-d H:i:s')) . '</p>
      <p class="meta"><strong>Generated by:</strong> ' . h((string)($_SESSION['username'] ?? '')) . '</p>
    </div>

    <table class="summary-grid">
      <tr>
        <td>
          <div class="summary-card">
            <div class="summary-label">Total Events</div>
            <div class="summary-value">' . h($totalEvents) . '</div>
          </div>
        </td>
        <td>
          <div class="summary-card">
            <div class="summary-label">Unique Sessions</div>
            <div class="summary-value">' . h($uniqueSessions) . '</div>
          </div>
        </td>
        <td>
          <div class="summary-card">
            <div class="summary-label">Top Event Type</div>
            <div class="summary-value">' . h((string)$topEventTypeValue) . '</div>
          </div>
        </td>
        <td>
          <div class="summary-card">
            <div class="summary-label">Most Active Origin</div>
            <div class="summary-value">' . h((string)$topOriginValue) . '</div>
          </div>
        </td>
      </tr>
    </table>

    <div class="report-section">
      <h2>1. Page Activity Report</h2>
      <p class="section-text">
        This section highlights which pages receive the highest number of tracked events and helps identify where visitor attention is concentrated.
      </p>
      ' . renderChartImage($chartPageActivity, 'Page Activity Chart') . '

      <div class="comment-box">
        <div class="comment-title">Analyst Comments</div>
        <div>' . nl2br(h($commentPageActivity !== '' ? $commentPageActivity : 'No analyst comment was provided for this section.')) . '</div>
      </div>

      <table class="data-table">
        <thead>
          <tr>
            <th>Page URL</th>
            <th>Event Count</th>
          </tr>
        </thead>
        <tbody>
';

foreach ($topPages as $row) {
    $html .= '
          <tr>
            <td>' . h((string)($row['page_url'] ?? '')) . '</td>
            <td>' . h((string)($row['views'] ?? 0)) . '</td>
          </tr>
    ';
}

$html .= '
        </tbody>
      </table>
    </div>

    <div class="report-section">
      <h2>2. Event Type Analysis</h2>
      <p class="section-text">
        This section shows the distribution of tracked event types so interaction patterns can be understood more clearly.
      </p>
      ' . renderChartImage($chartEventTypes, 'Event Types Chart') . '

      <div class="comment-box">
        <div class="comment-title">Analyst Comments</div>
        <div>' . nl2br(h($commentEventTypes !== '' ? $commentEventTypes : 'No analyst comment was provided for this section.')) . '</div>
      </div>

      <table class="data-table">
        <thead>
          <tr>
            <th>Event Type</th>
            <th>Count</th>
          </tr>
        </thead>
        <tbody>
';

foreach ($eventTypes as $row) {
    $html .= '
          <tr>
            <td>' . h((string)($row['event_type'] ?? '')) . '</td>
            <td>' . h((string)($row['total'] ?? 0)) . '</td>
          </tr>
    ';
}

$html .= '
        </tbody>
      </table>
    </div>

    <div class="report-section">
      <h2>3. Site Origin Analysis</h2>
      <p class="section-text">
        This section compares event volume across site origins so monitored environments can be evaluated side by side.
      </p>
      ' . renderChartImage($chartSiteOrigins, 'Site Origins Chart') . '

      <div class="comment-box">
        <div class="comment-title">Analyst Comments</div>
        <div>' . nl2br(h($commentSiteOrigin !== '' ? $commentSiteOrigin : 'No analyst comment was provided for this section.')) . '</div>
      </div>

      <table class="data-table">
        <thead>
          <tr>
            <th>Site Origin</th>
            <th>Count</th>
          </tr>
        </thead>
        <tbody>
';

foreach ($siteOrigins as $row) {
    $html .= '
          <tr>
            <td>' . h((string)($row['site_origin'] ?? '')) . '</td>
            <td>' . h((string)($row['total'] ?? 0)) . '</td>
          </tr>
    ';
}

$html .= '
        </tbody>
      </table>
    </div>

    <div class="report-section">
      <h2>Recent Events Snapshot</h2>
      <p class="section-text">
        This final table captures a recent sample of tracked events at the time of export.
      </p>

      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Received At</th>
            <th>Event Type</th>
            <th>Page URL</th>
            <th>Session ID</th>
          </tr>
        </thead>
        <tbody>
';

foreach ($recentEvents as $row) {
    $html .= '
          <tr>
            <td>' . h((string)($row['id'] ?? '')) . '</td>
            <td>' . h((string)($row['received_at'] ?? '')) . '</td>
            <td>' . h((string)($row['event_type'] ?? '')) . '</td>
            <td>' . h((string)($row['page_url'] ?? '')) . '</td>
            <td>' . h((string)($row['session_id'] ?? '')) . '</td>
          </tr>
    ';
}

$html .= '
        </tbody>
      </table>
    </div>

    <p class="small-note">
      End of analytics export report.
    </p>
  </div>
</body>
</html>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfOutput = $dompdf->output();

$result = file_put_contents($absolutePath, $pdfOutput);

if ($result === false) {
    http_response_code(500);
    exit('Failed to write export file: ' . $absolutePath);
}

$stmt = $pdo->prepare("
    INSERT INTO saved_reports (title, category, file_path)
    VALUES (:title, :category, :file_path)
");
$stmt->execute([
    ':title' => 'Analytics Report PDF Export',
    ':category' => $category,
    ':file_path' => $relativePath,
]);

header('Location: /saved_reports.php');
exit;

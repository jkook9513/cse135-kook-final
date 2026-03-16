<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_role(['viewer', 'super_admin', 'analyst']);

require_once __DIR__ . '/api/_db.php';

$pdo = db();

$stmt = $pdo->query("
    SELECT id, title, category, file_path, created_at
    FROM saved_reports
    ORDER BY created_at DESC
");
$reports = $stmt->fetchAll();

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Saved Reports</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --bg: #f6f8fb;
      --card: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --line: #d1d5db;
      --shadow: 0 8px 24px rgba(0,0,0,0.08);
      --radius: 14px;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .topbar {
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

    .page {
      max-width: 1100px;
      margin: 24px auto;
      padding: 0 20px 40px;
    }

    .hero,
    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }

    .hero {
      padding: 24px;
      margin-bottom: 22px;
    }

    .hero h1 {
      margin-top: 0;
      margin-bottom: 10px;
    }

    .hero p {
      color: var(--muted);
      margin: 6px 0;
    }

    .actions {
      margin-top: 16px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .actions a {
      text-decoration: none;
      color: #fff;
      background: #2563eb;
      padding: 10px 14px;
      border-radius: 10px;
      display: inline-block;
    }

    .actions a.secondary {
      background: #334155;
    }

    .card {
      padding: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid var(--line);
      padding: 10px 12px;
      text-align: left;
      vertical-align: top;
    }

    th {
      background: #f3f4f6;
    }

    .empty {
      color: var(--muted);
      padding: 14px 0;
    }

    .open-link {
      text-decoration: none;
      color: #2563eb;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <div>
        <strong>Saved Reports</strong><br>
        <span style="font-size:0.9rem; color:#cbd5e1;">
          Logged in as <?= h((string)($_SESSION['username'] ?? '')) ?>
          (<?= h((string)($_SESSION['role'] ?? '')) ?>)
        </span>
      </div>
      <div>
        <a href="logout.php" style="color:#fff;">Log Out</a>
      </div>
    </div>
  </div>

  <div class="page">
    <section class="hero">
      <h1>Saved Report Exports</h1>
      <p>
        This page lists previously exported analytics reports. Viewer users can access saved reports here without entering the live dashboard.
      </p>

      <div class="actions">
        <?php if (in_array($_SESSION['role'] ?? '', ['super_admin', 'analyst'], true)): ?>
          <a href="reports.php">Back to Dashboard</a>
        <?php endif; ?>
        <a href="logout.php" class="secondary">Log Out</a>
      </div>
    </section>

    <section class="card">
      <?php if (count($reports) === 0): ?>
        <div class="empty">No saved reports yet.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Category</th>
              <th>Created At</th>
              <th>File Path</th>
              <th>Open</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $report): ?>
              <tr>
                <td><?= h((string)($report['id'] ?? '')) ?></td>
                <td><?= h((string)($report['title'] ?? '')) ?></td>
                <td><?= h((string)($report['category'] ?? '')) ?></td>
                <td><?= h((string)($report['created_at'] ?? '')) ?></td>
                <td><?= h((string)($report['file_path'] ?? '')) ?></td>
                <td>
                  <a class="open-link" href="/<?= h(ltrim((string)($report['file_path'] ?? ''), '/')) ?>" target="_blank">
                    Open PDF
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>

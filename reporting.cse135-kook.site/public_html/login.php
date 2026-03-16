<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/api/_db.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    header('Location: ' . ($role === 'viewer' ? 'saved_reports.php' : 'reports.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header('Location: ' . ($user['role'] === 'viewer' ? 'saved_reports.php' : 'reports.php'));
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Analytics Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --bg: #f6f8fb;
      --card: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --line: #d1d5db;
      --accent: #2563eb;
      --accent-dark: #1d4ed8;
      --danger-bg: #fef2f2;
      --danger-line: #fecaca;
      --danger-text: #991b1b;
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
      min-height: 100vh;
      display: flex;
      flex-direction: column;
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

    .brand {
      font-size: 1.15rem;
      font-weight: bold;
    }

    .brand-subtitle {
      font-size: 0.9rem;
      color: #cbd5e1;
      margin-top: 2px;
    }

    .page {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
    }

    .login-shell {
      width: 100%;
      max-width: 1000px;
      display: grid;
      grid-template-columns: 1.15fr 0.95fr;
      gap: 24px;
      align-items: stretch;
    }

    .hero,
    .login-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }

    .hero {
      padding: 28px;
      background: linear-gradient(135deg, #dbeafe, #eff6ff);
      border-color: #bfdbfe;
    }

    .hero h1 {
      margin: 0 0 12px;
      font-size: 2rem;
      line-height: 1.15;
    }

    .hero p {
      margin: 10px 0;
      color: var(--muted);
      line-height: 1.6;
    }

    .hero-list {
      margin: 18px 0 0;
      padding-left: 18px;
      color: var(--text);
      line-height: 1.7;
    }

    .login-card {
      padding: 28px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-card h2 {
      margin: 0 0 10px;
      font-size: 1.6rem;
    }

    .login-card p {
      margin: 0 0 20px;
      color: var(--muted);
      line-height: 1.5;
    }

    .error-box {
      margin-bottom: 18px;
      padding: 12px 14px;
      border: 1px solid var(--danger-line);
      background: var(--danger-bg);
      color: var(--danger-text);
      border-radius: 10px;
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 16px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      font-size: 0.95rem;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--line);
      border-radius: 10px;
      font: inherit;
      color: var(--text);
      background: #fff;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .login-btn {
      width: 100%;
      border: none;
      background: var(--accent);
      color: #fff;
      padding: 12px 16px;
      border-radius: 10px;
      font: inherit;
      font-weight: bold;
      cursor: pointer;
      margin-top: 6px;
    }

    .login-btn:hover {
      background: var(--accent-dark);
    }

    .help-text {
      margin-top: 16px;
      color: var(--muted);
      font-size: 0.92rem;
      line-height: 1.5;
    }

    .footer-note {
      margin-top: 14px;
      font-size: 0.88rem;
      color: var(--muted);
    }

    @media (max-width: 900px) {
      .login-shell {
        grid-template-columns: 1fr;
        max-width: 560px;
      }

      .hero,
      .login-card {
        padding: 22px;
      }

      .hero h1 {
        font-size: 1.7rem;
      }
    }

    @media (max-width: 520px) {
      .page {
        padding: 20px 14px;
      }

      .topbar {
        padding: 12px 14px;
      }

      .hero,
      .login-card {
        padding: 18px;
      }

      .hero h1 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <div>
        <div class="brand">Analytics Reporting Platform</div>
        <div class="brand-subtitle">Secure access to reports, exports, and saved analytics snapshots</div>
      </div>
    </div>
  </div>

  <div class="page">
    <div class="login-shell">
      <section class="hero">
        <h1>Analytics access for reporting and exports</h1>
        <p>
          This portal provides access to the analytics dashboard, saved report exports,
          and role-based reporting workflows for the CSE135 analytics project.
        </p>
        <p>
          Analysts and administrators can review live visualizations, export styled PDF reports,
          and examine tracked activity across page usage, event types, and site origins.
        </p>

        <ul class="hero-list">
          <li>Role-based access for super admins, analysts, and viewers</li>
          <li>Dashboard visualizations with charts and data tables</li>
          <li>Saved PDF exports for later review</li>
        </ul>
      </section>

      <section class="login-card">
        <h2>Sign in</h2>
        <p>Enter your account credentials to continue to the analytics platform.</p>

        <?php if ($error !== ''): ?>
          <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <div class="form-group">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required autocomplete="username">
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
          </div>

          <button class="login-btn" type="submit">Log In</button>
        </form>

        <div class="help-text">
          Viewer accounts are directed to saved reports. Analyst and super admin accounts are directed to the live reporting dashboard.
        </div>

        <div class="footer-note">
          Access is restricted to authorized users only.
        </div>
      </section>
    </div>
  </div>
</body>
</html>

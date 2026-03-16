<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/_db.php';

$pdo = db();

$sql = "
    SELECT
        page_url,
        COUNT(*) AS views
    FROM collector_events
    WHERE page_url IS NOT NULL AND page_url <> ''
    GROUP BY page_url
    ORDER BY views DESC
    LIMIT 10
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($rows);

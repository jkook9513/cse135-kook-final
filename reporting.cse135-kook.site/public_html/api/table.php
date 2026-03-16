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
        id,
        received_at,
        site_origin,
        page_url,
        session_id,
        event_type
    FROM collector_events
    ORDER BY received_at DESC
    LIMIT 50
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($rows);

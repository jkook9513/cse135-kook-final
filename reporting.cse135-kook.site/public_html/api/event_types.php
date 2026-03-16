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
    SELECT event_type, COUNT(*) AS total
    FROM collector_events
    WHERE event_type IS NOT NULL AND event_type <> ''
    GROUP BY event_type
    ORDER BY total DESC
";
$stmt = $pdo->query($sql);

header('Content-Type: application/json');
echo json_encode($stmt->fetchAll());

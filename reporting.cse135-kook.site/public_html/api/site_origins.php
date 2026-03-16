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
    SELECT site_origin, COUNT(*) AS total
    FROM collector_events
    WHERE site_origin IS NOT NULL AND site_origin <> ''
    GROUP BY site_origin
    ORDER BY total DESC
";
$stmt = $pdo->query($sql);

header('Content-Type: application/json');
echo json_encode($stmt->fetchAll());

<?php
declare(strict_types=1);

require __DIR__ . "/../_db.php";

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

try {
  $pdo = db();

  if ($method === "GET") {
    $limit = isset($_GET["limit"]) ? max(1, min(200, (int)$_GET["limit"])) : 50;

    $stmt = $pdo->prepare(
      "SELECT id, received_at, site_origin, page_url, session_id, event_type
       FROM collector_events
       ORDER BY id DESC
       LIMIT :limit"
    );
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(["ok" => true, "data" => $stmt->fetchAll()]);
    exit;
  }

  if ($method === "POST") {
    $raw = file_get_contents("php://input") ?: "";
    $data = json_decode($raw, true);
    if (!is_array($data)) {
      http_response_code(400);
      echo json_encode(["ok" => false, "error" => "Invalid JSON"]);
      exit;
    }

    $stmt = $pdo->prepare(
      "INSERT INTO collector_events (site_origin, page_url, session_id, event_type, payload)
       VALUES (:site_origin, :page_url, :session_id, :event_type, :payload)"
    );
    $stmt->execute([
      ":site_origin" => $data["site_origin"] ?? null,
      ":page_url" => $data["page_url"] ?? null,
      ":session_id" => $data["session_id"] ?? null,
      ":event_type" => $data["event_type"] ?? null,
      ":payload" => json_encode($data["payload"] ?? new stdClass()),
    ]);

    echo json_encode(["ok" => true, "id" => (int)$pdo->lastInsertId()]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["ok" => false, "error" => "Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}

<?php
declare(strict_types=1);

require __DIR__ . "/../_db.php";

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Missing id"]);
  exit;
}

try {
  $pdo = db();

  if ($method === "GET") {
    $stmt = $pdo->prepare(
      "SELECT id, received_at, site_origin, page_url, session_id, event_type, payload
       FROM collector_events
       WHERE id = :id"
    );
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch();

    if (!$row) {
      http_response_code(404);
      echo json_encode(["ok" => false, "error" => "Not found"]);
      exit;
    }

    echo json_encode(["ok" => true, "data" => $row]);
    exit;
  }

  if ($method === "PUT") {
    $raw = file_get_contents("php://input") ?: "";
    $data = json_decode($raw, true);
    if (!is_array($data)) {
      http_response_code(400);
      echo json_encode(["ok" => false, "error" => "Invalid JSON"]);
      exit;
    }

    $stmt = $pdo->prepare(
      "UPDATE collector_events
       SET event_type = COALESCE(:event_type, event_type),
           page_url   = COALESCE(:page_url, page_url)
       WHERE id = :id"
    );
    $stmt->execute([
      ":event_type" => $data["event_type"] ?? null,
      ":page_url" => $data["page_url"] ?? null,
      ":id" => $id,
    ]);

    echo json_encode(["ok" => true, "updated" => $stmt->rowCount()]);
    exit;
  }

  if ($method === "DELETE") {
    $stmt = $pdo->prepare("DELETE FROM collector_events WHERE id = :id");
    $stmt->execute([":id" => $id]);
    echo json_encode(["ok" => true, "deleted" => $stmt->rowCount()]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["ok" => false, "error" => "Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}
